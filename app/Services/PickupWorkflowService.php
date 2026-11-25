<?php

namespace App\Services;

use App\Models\DonationCertificate;
use App\Models\DonationStatusHistory;
use App\Models\FoodDonation;
use App\Models\HandoverAuditLog;
use App\Models\NGO;
use App\Models\Notification;
use App\Models\PickupRequest;
use App\Models\PickupVerification;
use App\Models\Restaurant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PickupWorkflowService
{
    public function __construct(
        protected HandoverVerificationService $verificationService,
        protected CertificateService $certificateService
    ) {}

    /**
     * Step 1: NGO requests available food donation.
     * Transitions: Food available -> requested; PickupRequest created as pending.
     */
    public function requestPickup(FoodDonation $donation, NGO $ngo, array $data, User $user): PickupRequest
    {
        if ($ngo->status !== 'active' || $user->status !== 'active') {
            throw new \InvalidArgumentException('Your NGO account must be approved and active to request food pickups.');
        }

        if ($ngo->isRegistrationExpired()) {
            throw new \InvalidArgumentException('Your NGO registration certificate has expired. Please update your compliance documents.');
        }

        return DB::transaction(function () use ($donation, $ngo, $data, $user) {
            // Lock food donation row
            $lockedDonation = FoodDonation::where('id', $donation->id)->lockForUpdate()->firstOrFail();

            if ($lockedDonation->status !== 'available') {
                throw new \InvalidArgumentException('This food donation is no longer available.');
            }

            if ($lockedDonation->expiry_time && $lockedDonation->expiry_time->isPast()) {
                $lockedDonation->update(['status' => 'expired']);
                throw new \InvalidArgumentException('This food donation has expired and cannot be requested.');
            }

            // Check if NGO already has an active request for this donation
            $existing = PickupRequest::where('food_donation_id', $lockedDonation->id)
                ->where('ngo_id', $ngo->id)
                ->whereIn('status', ['pending', 'accepted', 'out_for_pickup', 'handover_confirmed'])
                ->first();

            if ($existing) {
                throw new \InvalidArgumentException('You already have an active pickup request for this donation.');
            }

            $pickupRequest = PickupRequest::create([
                'food_donation_id' => $lockedDonation->id,
                'ngo_id' => $ngo->id,
                'pickup_time' => $data['pickup_time'],
                'remarks' => $data['remarks'] ?? null,
                'status' => 'pending',
            ]);

            $lockedDonation->update(['status' => 'requested']);

            DonationStatusHistory::create([
                'pickup_request_id' => $pickupRequest->id,
                'food_donation_id' => $lockedDonation->id,
                'status' => 'pending',
                'changed_by_user_id' => $user->id,
                'notes' => 'Pickup request submitted by ' . $ngo->ngo_name,
            ]);

            HandoverAuditLog::log(
                action: 'request_submitted',
                pickupRequest: $pickupRequest,
                user: $user,
                statusBefore: 'available',
                statusAfter: 'pending',
                details: ['pickup_time' => (string) $data['pickup_time']]
            );

            // Notify Restaurant donor
            if ($lockedDonation->restaurant?->user) {
                Notification::create([
                    'user_id' => $lockedDonation->restaurant->user->id,
                    'type' => 'new_pickup_request',
                    'title' => 'New Pickup Request',
                    'message' => "{$ngo->ngo_name} has requested pickup for \"{$lockedDonation->food_name}\".",
                    'link' => route('restaurant.history'),
                    'food_donation_id' => $lockedDonation->id,
                    'is_read' => false,
                ]);
            }

            return $pickupRequest;
        });
    }

    /**
     * Step 2: Restaurant accepts pickup request.
     * Transitions: PickupRequest pending -> accepted; Food requested -> approved.
     * Generates OTP and QR code tokens for handover.
     */
    public function acceptRequest(PickupRequest $pickupRequest, User $actor, ?string $remarks = null): PickupRequest
    {
        $pickupRequest->loadMissing(['foodDonation.restaurant.user', 'ngo.user']);
        $restaurant = $pickupRequest->foodDonation?->restaurant;

        $isOwner = ($actor->role === 'restaurant' && $restaurant?->user_id === $actor->id);
        $isAdmin = ($actor->role === 'admin');

        if (!$isOwner && !$isAdmin) {
            throw new \InvalidArgumentException('Unauthorized: Only the donor restaurant or admin can accept this pickup request.');
        }

        if ($pickupRequest->status !== 'pending') {
            throw new \InvalidArgumentException("Cannot accept request in status '{$pickupRequest->status}'. Must be 'pending'.");
        }

        $donation = $pickupRequest->foodDonation;
        if ($donation && $donation->expiry_time && $donation->expiry_time->isPast()) {
            throw new \InvalidArgumentException('Cannot accept request because the food has expired.');
        }

        return DB::transaction(function () use ($pickupRequest, $donation, $actor, $remarks) {
            $statusBefore = $pickupRequest->status;
            $restaurant = $donation?->restaurant;

            $pickupRequest->update([
                'status' => 'accepted',
                'remarks' => $remarks ?? $pickupRequest->remarks,
            ]);

            if ($donation) {
                $donation->update(['status' => 'approved']);
            }

            // Reject any other pending requests for this same donation
            $otherRequests = PickupRequest::where('food_donation_id', $pickupRequest->food_donation_id)
                ->where('id', '!=', $pickupRequest->id)
                ->where('status', 'pending')
                ->get();

            foreach ($otherRequests as $other) {
                $other->update([
                    'status' => 'rejected',
                    'remarks' => 'Another NGO pickup request was accepted for this donation.',
                ]);
            }

            // Generate initial secure OTP and QR verification tokens
            $this->verificationService->getOrCreateVerification($pickupRequest);

            DonationStatusHistory::create([
                'pickup_request_id' => $pickupRequest->id,
                'food_donation_id' => $pickupRequest->food_donation_id,
                'status' => 'accepted',
                'changed_by_user_id' => $actor->id,
                'notes' => 'Request accepted by donor restaurant. OTP and QR code generated.',
            ]);

            HandoverAuditLog::log(
                action: 'request_accepted',
                pickupRequest: $pickupRequest,
                user: $actor,
                statusBefore: $statusBefore,
                statusAfter: 'accepted'
            );

            // Notify assigned NGO
            if ($pickupRequest->ngo?->user) {
                Notification::create([
                    'user_id' => $pickupRequest->ngo->user->id,
                    'type' => 'request_accepted',
                    'title' => 'Pickup Request Accepted!',
                    'message' => "Your request for \"{$donation->food_name}\" was accepted by {$restaurant->restaurant_name}. Please start pickup journey when ready.",
                    'link' => route('ngo.history'),
                    'food_donation_id' => $donation->id,
                    'is_read' => false,
                ]);
            }

            return $pickupRequest;
        });
    }

    /**
     * Reject or Cancel request.
     */
    public function rejectRequest(PickupRequest $pickupRequest, User $actor, string $reason): PickupRequest
    {
        $pickupRequest->loadMissing(['foodDonation.restaurant.user', 'ngo.user']);

        $isRestaurant = ($actor->role === 'restaurant' && $pickupRequest->foodDonation?->restaurant?->user_id === $actor->id);
        $isNgo = ($actor->role === 'ngo' && $pickupRequest->ngo?->user_id === $actor->id);
        $isAdmin = ($actor->role === 'admin');

        if (!$isRestaurant && !$isNgo && !$isAdmin) {
            throw new \InvalidArgumentException('Unauthorized to reject or cancel this pickup request.');
        }

        // Cannot cancel if handover already confirmed or completed
        if (in_array($pickupRequest->status, ['handover_confirmed', 'completed'])) {
            throw new \InvalidArgumentException('Cannot cancel or reject a pickup request after physical handover has been verified.');
        }

        return DB::transaction(function () use ($pickupRequest, $actor, $reason, $isNgo) {
            $statusBefore = $pickupRequest->status;
            $newStatus = 'rejected';

            $pickupRequest->update([
                'status' => $newStatus,
                'remarks' => ($pickupRequest->remarks ? $pickupRequest->remarks . ' | ' : '') . $reason,
            ]);

            // Release donation back to available if not expired and no other accepted request
            $donation = $pickupRequest->foodDonation;
            if ($donation && $donation->status !== 'expired') {
                $hasOtherAccepted = PickupRequest::where('food_donation_id', $donation->id)
                    ->where('id', '!=', $pickupRequest->id)
                    ->whereIn('status', ['accepted', 'out_for_pickup'])
                    ->exists();

                if (!$hasOtherAccepted) {
                    $donation->update(['status' => 'available']);
                }
            }

            // Invalidate any pending verification tokens
            PickupVerification::where('pickup_request_id', $pickupRequest->id)
                ->where('status', 'pending')
                ->update(['status' => 'cancelled']);

            DonationStatusHistory::create([
                'pickup_request_id' => $pickupRequest->id,
                'food_donation_id' => $pickupRequest->food_donation_id,
                'status' => 'rejected',
                'changed_by_user_id' => $actor->id,
                'notes' => ($isNgo ? 'Cancelled by NGO: ' : 'Declined by Restaurant: ') . $reason,
            ]);

            HandoverAuditLog::log(
                action: $isNgo ? 'request_cancelled_by_ngo' : 'request_rejected_by_restaurant',
                pickupRequest: $pickupRequest,
                user: $actor,
                statusBefore: $statusBefore,
                statusAfter: 'rejected',
                details: ['reason' => $reason]
            );

            return $pickupRequest;
        });
    }

    /**
     * Step 3: NGO Volunteer starts pickup journey.
     * Transitions: PickupRequest accepted -> out_for_pickup.
     */
    public function startPickup(PickupRequest $pickupRequest, User $actor): PickupRequest
    {
        $pickupRequest->loadMissing(['foodDonation.restaurant.user', 'ngo.user']);

        if ($actor->role !== 'ngo' || $actor->ngo?->id !== $pickupRequest->ngo_id) {
            throw new \InvalidArgumentException('Unauthorized: Only the assigned NGO can start pickup for this request.');
        }

        if ($pickupRequest->status !== 'accepted') {
            throw new \InvalidArgumentException("Cannot start pickup for request in status '{$pickupRequest->status}'. Must be 'accepted'.");
        }

        $donation = $pickupRequest->foodDonation;
        if ($donation && $donation->expiry_time && $donation->expiry_time->isPast()) {
            throw new \InvalidArgumentException('Cannot start pickup because the food donation has expired.');
        }

        return DB::transaction(function () use ($pickupRequest, $donation, $actor) {
            $statusBefore = $pickupRequest->status;
            $pickupRequest->update(['status' => 'out_for_pickup']);

            // Ensure verification token exists
            $this->verificationService->getOrCreateVerification($pickupRequest);

            DonationStatusHistory::create([
                'pickup_request_id' => $pickupRequest->id,
                'food_donation_id' => $pickupRequest->food_donation_id,
                'status' => 'out_for_pickup',
                'changed_by_user_id' => $actor->id,
                'notes' => 'Volunteer is en route for collection.',
            ]);

            HandoverAuditLog::log(
                action: 'out_for_pickup',
                pickupRequest: $pickupRequest,
                user: $actor,
                statusBefore: $statusBefore,
                statusAfter: 'out_for_pickup'
            );

            // Notify Restaurant
            if ($donation?->restaurant?->user) {
                Notification::create([
                    'user_id' => $donation->restaurant->user->id,
                    'type' => 'pickup_en_route',
                    'title' => 'Volunteer En Route',
                    'message' => "{$pickupRequest->ngo->ngo_name} is on the way for \"{$donation->food_name}\". Your handover OTP & QR are ready on your dashboard.",
                    'link' => route('restaurant.history'),
                    'food_donation_id' => $donation->id,
                    'is_read' => false,
                ]);
            }

            return $pickupRequest;
        });
    }

    /**
     * Step 4: Physical Handover Verification via OTP or QR code.
     * Transitions: PickupRequest out_for_pickup/accepted -> handover_confirmed; Food -> picked.
     * Can ONLY be achieved through OTP or QR verification or audited admin override.
     */
    public function verifyHandoverByOtp(PickupRequest $pickupRequest, string $otp, User $volunteer): array
    {
        return $this->verificationService->verifyByOtp($pickupRequest, $otp, $volunteer);
    }

    public function verifyHandoverByQr(string $qrToken, User $volunteer): array
    {
        return $this->verificationService->verifyByQrToken($qrToken, $volunteer);
    }

    /**
     * Step 5: Complete Pickup & Issue Certificate.
     * Transitions: PickupRequest handover_confirmed -> completed; Food -> completed.
     * STRICT REQUIREMENT: Can ONLY complete if handover was legitimately verified!
     */
    public function completePickup(PickupRequest $pickupRequest, User $actor): DonationCertificate
    {
        $pickupRequest->loadMissing(['foodDonation.restaurant.user', 'ngo.user', 'verification']);

        $isRestaurant = ($actor->role === 'restaurant' && $pickupRequest->foodDonation?->restaurant?->user_id === $actor->id);
        $isNgo = ($actor->role === 'ngo' && $pickupRequest->ngo?->user_id === $actor->id);
        $isAdmin = ($actor->role === 'admin');

        if (!$isRestaurant && !$isNgo && !$isAdmin) {
            throw new \InvalidArgumentException('Unauthorized to complete this pickup.');
        }

        // STRICT REQUIREMENT: Must be in 'handover_confirmed'
        if ($pickupRequest->status !== 'handover_confirmed') {
            throw new \InvalidArgumentException("Cannot complete pickup in status '{$pickupRequest->status}'. Handover OTP or QR code must be verified first.");
        }

        // Verify that a valid verified record exists
        $verification = $pickupRequest->verification ?? PickupVerification::where('pickup_request_id', $pickupRequest->id)->first();
        if (!$verification || $verification->status !== 'verified') {
            throw new \InvalidArgumentException('Cannot complete donation without a verified handover record.');
        }

        return DB::transaction(function () use ($pickupRequest, $actor) {
            $statusBefore = $pickupRequest->status;
            $pickupRequest->update(['status' => 'completed']);
            $pickupRequest->foodDonation?->update(['status' => 'completed']);

            DonationStatusHistory::create([
                'pickup_request_id' => $pickupRequest->id,
                'food_donation_id' => $pickupRequest->food_donation_id,
                'status' => 'completed',
                'changed_by_user_id' => $actor->id,
                'notes' => 'Food rescue distribution confirmed and closed. Certificate issued.',
            ]);

            HandoverAuditLog::log(
                action: 'donation_completed',
                pickupRequest: $pickupRequest,
                user: $actor,
                statusBefore: $statusBefore,
                statusAfter: 'completed'
            );

            // Automatically generate official PDF Donation Certificate
            $certificate = $this->certificateService->generateForPickup($pickupRequest);

            // Notify Restaurant
            if ($pickupRequest->foodDonation?->restaurant?->user_id) {
                Notification::create([
                    'user_id' => $pickupRequest->foodDonation->restaurant->user_id,
                    'type' => 'certificate_ready',
                    'title' => 'Donation Certificate Available',
                    'message' => "Donation of \"{$pickupRequest->foodDonation->food_name}\" is fully completed! Your official Donation Certificate ({$certificate->certificate_number}) is ready to download.",
                    'link' => route('certificates.download', $certificate->id),
                    'food_donation_id' => $pickupRequest->food_donation_id,
                    'is_read' => false,
                ]);
            }

            // Notify NGO
            if ($pickupRequest->ngo?->user_id) {
                Notification::create([
                    'user_id' => $pickupRequest->ngo->user_id,
                    'type' => 'donation_completed',
                    'title' => 'Donation Completed & Certificate Issued',
                    'message' => "Thank you for completing food rescue for \"{$pickupRequest->foodDonation->food_name}\"! You can now leave a rating and review.",
                    'link' => route('ngo.history'),
                    'food_donation_id' => $pickupRequest->food_donation_id,
                    'is_read' => false,
                ]);
            }

            return $certificate;
        });
    }
}
