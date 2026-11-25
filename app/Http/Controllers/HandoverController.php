<?php

namespace App\Http\Controllers;

use App\Models\PickupRequest;
use App\Services\CertificateService;
use App\Services\HandoverVerificationService;
use App\Services\PickupWorkflowService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HandoverController extends Controller
{
    public function __construct(
        protected HandoverVerificationService $verificationService,
        protected PickupWorkflowService $workflowService,
        protected CertificateService $certificateService
    ) {}

    /**
     * NGO marks that volunteer has started pickup journey (Status: out_for_pickup).
     */
    public function startPickup(Request $request, $id)
    {
        $user = auth()->user();
        $pickupRequest = PickupRequest::with(['foodDonation.restaurant.user', 'ngo'])->findOrFail($id);

        try {
            $this->workflowService->startPickup($pickupRequest, $user);
            return back()->with('success', 'Pickup status updated: Out for Pickup. Please present the OTP or scan the QR code upon arrival at the restaurant.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Return OTP & QR code verification payload for Restaurant display.
     * Strictly restricted: ONLY authorized restaurant owner of the donation or admin!
     * NGO and public users are strictly blocked (HTTP 403).
     */
    public function getVerificationData($id)
    {
        $user = auth()->user();
        $pickupRequest = PickupRequest::with(['foodDonation.restaurant', 'ngo.user', 'verification'])->findOrFail($id);

        $isRestaurant = ($user->role === 'restaurant' && $user->restaurant?->id === $pickupRequest->foodDonation?->restaurant_id);
        $isAdmin = ($user->role === 'admin');

        if (!$isRestaurant && !$isAdmin) {
            return response()->json([
                'error' => 'Unauthorized access. Only the donor restaurant owner can view pickup OTP and QR code.',
            ], 403);
        }

        $verification = $this->verificationService->getOrCreateVerification($pickupRequest);
        $qrDataUri = $this->verificationService->getQrCodeDataUri($verification);

        return response()->json([
            'id' => $verification->id,
            'otp_code' => $verification->getDecryptedOtp(),
            'qr_data_uri' => $qrDataUri,
            'expires_at' => $verification->otp_expires_at?->toIso8601String(),
            'seconds_remaining' => max(0, Carbon::now()->diffInSeconds($verification->otp_expires_at, false)),
            'status' => $verification->status,
            'is_verified' => $verification->isVerified(),
            'can_resend' => (!$verification->resend_available_at || $verification->resend_available_at->isPast()),
        ]);
    }

    /**
     * NGO Volunteer submits OTP to confirm handover.
     */
    public function verifyOtp(Request $request, $id)
    {
        $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        $pickupRequest = PickupRequest::findOrFail($id);
        $result = $this->workflowService->verifyHandoverByOtp($pickupRequest, $request->otp, auth()->user());

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * QR Code verification endpoint when scanned by volunteer.
     */
    public function verifyQr(Request $request, $token)
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login')->with('info', 'Please log in with your assigned NGO account to verify this pickup QR code.');
        }

        if ($user->role !== 'ngo') {
            return redirect()->route('dashboard')->with('error', 'Unauthorized: Only verified NGO volunteers can scan and verify pickup QR codes.');
        }

        $result = $this->workflowService->verifyHandoverByQr($token, $user);

        if (!$result['success']) {
            return redirect()->route('ngo.history')->with('error', $result['message']);
        }

        return redirect()->route('ngo.history')->with('success', $result['message']);
    }

    /**
     * Restaurant regenerates/resends a fresh OTP.
     */
    public function resendOtp(Request $request, $id)
    {
        $pickupRequest = PickupRequest::findOrFail($id);
        $result = $this->verificationService->resendOtp($pickupRequest, auth()->user());

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Mark donation as Completed after handover.
     * STRICT REQUIREMENT: Handover must be verified first!
     */
    public function completeDonation(Request $request, $id)
    {
        $user = auth()->user();
        $pickupRequest = PickupRequest::with(['foodDonation.restaurant.user', 'ngo.user', 'verification'])->findOrFail($id);

        try {
            $certificate = $this->workflowService->completePickup($pickupRequest, $user);
            return back()->with('success', "Donation successfully marked as Completed! Official Certificate #{$certificate->certificate_number} has been generated.");
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
