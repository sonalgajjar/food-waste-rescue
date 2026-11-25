<?php

namespace App\Services;

use App\Models\DonationStatusHistory;
use App\Models\HandoverAuditLog;
use App\Models\PickupRequest;
use App\Models\PickupVerification;
use App\Models\User;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class HandoverVerificationService
{
    public const OTP_EXPIRY_MINUTES = 10;
    public const RESEND_COOLDOWN_SECONDS = 60;
    public const MAX_ATTEMPTS = 5;

    public function __construct(
        protected QRCodeService $qrCodeService
    ) {}

    /**
     * Get or generate the active verification record for an accepted / out_for_pickup request.
     */
    public function getOrCreateVerification(PickupRequest $pickupRequest): PickupVerification
    {
        $pickupRequest->loadMissing(['foodDonation.restaurant', 'ngo']);

        $verification = PickupVerification::where('pickup_request_id', $pickupRequest->id)
            ->latest()
            ->first();

        // If no verification exists yet or expired while pending, create a fresh one
        if (!$verification) {
            $verification = $this->createVerification($pickupRequest);
        } elseif ($verification->status === 'pending' && $verification->isExpired()) {
            $verification->update([
                'status' => 'expired',
                'otp_encrypted' => null,
                'otp_hash' => null,
                'qr_token' => null,
            ]);
            $verification = $this->createVerification($pickupRequest);
        }

        return $verification;
    }

    /**
     * Create a fresh unique 6-digit OTP and 64-char QR token for the pickup.
     * Generates cryptographic hash and encrypted payload. Never stores plain text.
     */
    public function createVerification(PickupRequest $pickupRequest): PickupVerification
    {
        $otp = sprintf('%06d', random_int(100000, 999999));
        $rawQrToken = Str::random(64);

        $verification = PickupVerification::create([
            'pickup_request_id' => $pickupRequest->id,
            'food_donation_id' => $pickupRequest->food_donation_id,
            'restaurant_id' => $pickupRequest->foodDonation->restaurant_id,
            'ngo_id' => $pickupRequest->ngo_id,
            'otp_code' => null, // Plain text is strictly NEVER stored
            'otp_hash' => Hash::make($otp),
            'otp_encrypted' => Crypt::encryptString($otp),
            'qr_token' => hash('sha256', $rawQrToken),
            'qr_token_encrypted' => Crypt::encryptString($rawQrToken),
            'otp_expires_at' => Carbon::now()->addMinutes(self::OTP_EXPIRY_MINUTES),
            'resend_available_at' => Carbon::now()->addSeconds(self::RESEND_COOLDOWN_SECONDS),
            'attempts' => 0,
            'status' => 'pending',
        ]);

        HandoverAuditLog::log(
            action: 'otp_generated',
            pickupRequest: $pickupRequest,
            details: [
                'verification_id' => $verification->id,
                'expires_at' => $verification->otp_expires_at->toIso8601String(),
            ]
        );

        return $verification;
    }

    /**
     * Resend/Regenerate OTP if cooldown period has passed.
     * Strictly verifies that the requester is the authorized restaurant owner or admin.
     */
    public function resendOtp(PickupRequest $pickupRequest, User $requester): array
    {
        $pickupRequest->loadMissing(['foodDonation.restaurant', 'ngo']);

        // Strict authorization: Only donor restaurant owner or admin
        $isRestaurant = ($requester->role === 'restaurant' && $requester->restaurant?->id === $pickupRequest->foodDonation?->restaurant_id);
        $isAdmin = ($requester->role === 'admin');

        if (!$isRestaurant && !$isAdmin) {
            return [
                'success' => false,
                'message' => 'Unauthorized: Only the authorized donor restaurant can resend or regenerate the pickup OTP.',
            ];
        }

        if (!in_array($pickupRequest->status, ['accepted', 'out_for_pickup'])) {
            return [
                'success' => false,
                'message' => 'OTP can only be regenerated for active, accepted pickup requests.',
            ];
        }

        $verification = $this->getOrCreateVerification($pickupRequest);

        if ($verification->isVerified()) {
            return ['success' => false, 'message' => 'Handover has already been verified for this pickup.'];
        }

        if ($verification->resend_available_at && $verification->resend_available_at->isFuture()) {
            $remaining = Carbon::now()->diffInSeconds($verification->resend_available_at);
            return [
                'success' => false,
                'message' => "Please wait {$remaining} seconds before requesting a new OTP.",
                'cooldown' => $remaining,
            ];
        }

        $newOtp = sprintf('%06d', random_int(100000, 999999));
        $newRawQrToken = Str::random(64);
        $verification->update([
            'otp_code' => null,
            'otp_hash' => Hash::make($newOtp),
            'otp_encrypted' => Crypt::encryptString($newOtp),
            'qr_token' => hash('sha256', $newRawQrToken),
            'qr_token_encrypted' => Crypt::encryptString($newRawQrToken),
            'otp_expires_at' => Carbon::now()->addMinutes(self::OTP_EXPIRY_MINUTES),
            'resend_available_at' => Carbon::now()->addSeconds(self::RESEND_COOLDOWN_SECONDS),
            'attempts' => 0,
            'status' => 'pending',
        ]);

        HandoverAuditLog::log(
            action: 'otp_resend',
            pickupRequest: $pickupRequest,
            user: $requester,
            details: [
                'verification_id' => $verification->id,
                'new_expiry' => $verification->otp_expires_at->toIso8601String(),
            ]
        );

        return [
            'success' => true,
            'message' => 'A new pickup OTP and QR code have been generated successfully.',
            'verification' => $verification,
        ];
    }

    /**
     * Verify pickup handover using the 6-digit OTP code with transaction and row lock.
     */
    public function verifyByOtp(PickupRequest $pickupRequest, string $otp, User $volunteer): array
    {
        return DB::transaction(function () use ($pickupRequest, $otp, $volunteer) {
            $pickupRequest->loadMissing(['foodDonation.restaurant', 'ngo']);

            // 1. User role & account status validation
            if ($volunteer->role !== 'ngo') {
                return ['success' => false, 'message' => 'Unauthorized: Only verified NGO volunteers can verify handover.'];
            }

            if ($volunteer->status !== 'active') {
                return ['success' => false, 'message' => 'Unauthorized: Your user account is not active.'];
            }

            if ($volunteer->ngo && $volunteer->ngo->status !== 'active') {
                return ['success' => false, 'message' => 'Unauthorized: Your NGO organization account is pending or suspended.'];
            }

            // 2. NGO assignment check
            if ($volunteer->ngo?->id !== $pickupRequest->ngo_id) {
                return ['success' => false, 'message' => 'Unauthorized: You are not assigned to this pickup request.'];
            }

            // 3. Workflow status check
            if (!in_array($pickupRequest->status, ['accepted', 'out_for_pickup'])) {
                return ['success' => false, 'message' => "Pickup request is not in a verifiable state (Current status: {$pickupRequest->status})."];
            }

            $verification = PickupVerification::where('pickup_request_id', $pickupRequest->id)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->latest()
                ->first();

            if (!$verification) {
                return ['success' => false, 'message' => 'No active pending verification found for this pickup request.'];
            }

            if ($verification->isExpired()) {
                $verification->update([
                    'status' => 'expired',
                    'otp_encrypted' => null,
                    'otp_hash' => null,
                    'qr_token' => null,
                ]);
                return ['success' => false, 'message' => 'OTP has expired. Please ask the restaurant to regenerate a new OTP.'];
            }

            if ($verification->attempts >= self::MAX_ATTEMPTS) {
                $verification->update(['status' => 'failed']);
                return ['success' => false, 'message' => 'Maximum incorrect OTP attempts exceeded. Please request a new OTP.'];
            }

            // Secure cryptographic hash check ONLY - no plaintext fallback permitted
            $cleanOtp = trim($otp);
            if (!$verification->otp_hash || !Hash::check($cleanOtp, $verification->otp_hash)) {
                $verification->increment('attempts');
                $remaining = max(0, self::MAX_ATTEMPTS - $verification->attempts);

                HandoverAuditLog::log(
                    action: 'otp_failed_attempt',
                    pickupRequest: $pickupRequest,
                    user: $volunteer,
                    method: 'otp',
                    details: ['attempts' => $verification->attempts]
                );

                return [
                    'success' => false,
                    'message' => "Invalid OTP entered. {$remaining} attempt(s) remaining before lock.",
                ];
            }

            // Successful verification
            return $this->finalizeHandover($pickupRequest, $verification, $volunteer, 'otp');
        });
    }

    /**
     * Verify pickup handover using QR code token with transaction and row lock.
     */
    public function verifyByQrToken(string $qrToken, User $volunteer): array
    {
        return DB::transaction(function () use ($qrToken, $volunteer) {
            // 1. User validation
            if ($volunteer->role !== 'ngo') {
                return ['success' => false, 'message' => 'Unauthorized: Only verified NGO volunteers can verify handover.'];
            }

            if ($volunteer->status !== 'active') {
                return ['success' => false, 'message' => 'Unauthorized: Your account is not active.'];
            }

            if ($volunteer->ngo && $volunteer->ngo->status !== 'active') {
                return ['success' => false, 'message' => 'Unauthorized: Your NGO is not approved or active.'];
            }

            $hashedToken = hash('sha256', $qrToken);
            $verification = PickupVerification::where(function ($q) use ($hashedToken, $qrToken) {
                    $q->where('qr_token', $hashedToken)
                      ->orWhere('qr_token', $qrToken);
                })
                ->where('status', 'pending')
                ->lockForUpdate()
                ->first();

            if (!$verification) {
                return ['success' => false, 'message' => 'Invalid or already used QR verification code.'];
            }

            if ($verification->isExpired()) {
                $verification->update([
                    'status' => 'expired',
                    'otp_encrypted' => null,
                    'otp_hash' => null,
                    'qr_token' => null,
                    'qr_token_encrypted' => null,
                ]);
                return ['success' => false, 'message' => 'QR code has expired. Please ask the restaurant to refresh their QR code.'];
            }

            $pickupRequest = $verification->pickupRequest;
            $pickupRequest->loadMissing(['foodDonation.restaurant', 'ngo']);

            // 2. Assignment check
            if ($volunteer->ngo?->id !== $pickupRequest->ngo_id) {
                return ['success' => false, 'message' => 'Unauthorized: You are not assigned to this pickup request.'];
            }

            // 3. Workflow status check
            if (!in_array($pickupRequest->status, ['accepted', 'out_for_pickup'])) {
                return ['success' => false, 'message' => "Pickup request is not in a verifiable state (Current status: {$pickupRequest->status})."];
            }

            return $this->finalizeHandover($pickupRequest, $verification, $volunteer, 'qr');
        });
    }

    /**
     * Finalize handover: update statuses, invalidate sensitive OTP/QR tokens, and record immutable audit logs.
     */
    protected function finalizeHandover(
        PickupRequest $pickupRequest,
        PickupVerification $verification,
        User $volunteer,
        string $method
    ): array {
        $now = Carbon::now();
        // IMPORTANT: Capture original status BEFORE making database changes!
        $statusBefore = $pickupRequest->status;

        // 1. Invalidate tokens immediately to prevent replay / reuse
        $verification->update([
            'volunteer_id' => $volunteer->id,
            'verification_method' => $method,
            'verified_at' => $now,
            'status' => 'verified',
            'otp_code' => null,
            'otp_hash' => null,
            'otp_encrypted' => null,
            'qr_token' => null,
            'qr_token_encrypted' => null,
        ]);

        // 2. Transition pickup request to 'handover_confirmed'
        $pickupRequest->update([
            'status' => 'handover_confirmed',
        ]);

        // 3. Update donation status to 'picked'
        $donation = $pickupRequest->foodDonation;
        if ($donation) {
            $donation->update(['status' => 'picked']);
        }

        // 4. Log accurate audit trail with correct statusBefore captured prior to update
        HandoverAuditLog::log(
            action: $method === 'qr' ? 'qr_verified' : 'otp_verified',
            pickupRequest: $pickupRequest,
            user: $volunteer,
            method: $method,
            statusBefore: $statusBefore,
            statusAfter: 'handover_confirmed',
            details: [
                'verification_id' => $verification->id,
                'volunteer_name' => $volunteer->name,
                'volunteer_id' => $volunteer->id,
                'timestamp' => $now->toIso8601String(),
            ]
        );

        // 5. Log status history
        DonationStatusHistory::create([
            'pickup_request_id' => $pickupRequest->id,
            'food_donation_id' => $pickupRequest->food_donation_id,
            'status' => 'handover_confirmed',
            'changed_by_user_id' => $volunteer->id,
            'notes' => "Handover verified via {$method} by {$volunteer->name} (#{$volunteer->id}).",
        ]);

        // 6. In-app notifications
        $restaurantUser = $donation?->restaurant?->user;
        if ($restaurantUser) {
            Notification::create([
                'user_id' => $restaurantUser->id,
                'type' => 'handover_confirmed',
                'title' => 'Handover Confirmed',
                'message' => "Food handover for \"{$donation->food_name}\" was verified via {$method} by {$volunteer->name}.",
                'link' => route('restaurant.history'),
                'food_donation_id' => $donation->id,
                'is_read' => false,
            ]);
        }

        return [
            'success' => true,
            'message' => 'Pickup handover verified successfully! Donation status updated to Handover Confirmed.',
            'verification' => $verification,
        ];
    }

    /**
     * Administrative audited override for emergency dispute resolution.
     */
    public function adminOverrideHandover(PickupRequest $pickupRequest, User $admin, string $reason): array
    {
        if ($admin->role !== 'admin') {
            return ['success' => false, 'message' => 'Only authorized administrators can perform overrides.'];
        }

        return DB::transaction(function () use ($pickupRequest, $admin, $reason) {
            $verification = $this->getOrCreateVerification($pickupRequest);
            $now = Carbon::now();

            $verification->update([
                'volunteer_id' => $admin->id,
                'verification_method' => 'admin_override',
                'verified_at' => $now,
                'status' => 'verified',
                'otp_code' => null,
                'otp_hash' => null,
                'otp_encrypted' => null,
                'qr_token' => null,
            ]);

            $pickupRequest->update(['status' => 'handover_confirmed']);
            $pickupRequest->foodDonation?->update(['status' => 'picked']);

            HandoverAuditLog::log(
                action: 'admin_override',
                pickupRequest: $pickupRequest,
                user: $admin,
                method: 'admin_override',
                statusBefore: $pickupRequest->status,
                statusAfter: 'handover_confirmed',
                details: ['reason' => $reason]
            );

            DonationStatusHistory::create([
                'pickup_request_id' => $pickupRequest->id,
                'food_donation_id' => $pickupRequest->food_donation_id,
                'status' => 'handover_confirmed',
                'changed_by_user_id' => $admin->id,
                'notes' => "ADMIN OVERRIDE by {$admin->name} (#{$admin->id}). Reason: {$reason}",
            ]);

            return ['success' => true, 'message' => 'Administrative handover override recorded successfully.'];
        });
    }

    /**
     * Get QR Code Data URI for display in blade views.
     */
    public function getQrCodeDataUri(PickupVerification $verification): ?string
    {
        if (!$verification->qr_token) {
            return null;
        }
        $verifyUrl = route('handover.verify.qr', ['token' => $verification->qr_token]);
        return $this->qrCodeService->generateDataUri($verifyUrl, 220);
    }
}
