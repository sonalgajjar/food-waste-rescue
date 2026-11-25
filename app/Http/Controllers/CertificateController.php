<?php

namespace App\Http\Controllers;

use App\Models\DonationCertificate;
use App\Services\CertificateService;
use App\Services\QRCodeService;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    public function __construct(
        protected CertificateService $certificateService,
        protected QRCodeService $qrCodeService
    ) {}

    /**
     * Download certificate PDF.
     */
    public function download($id)
    {
        $user = auth()->user();
        $certificate = DonationCertificate::with([
            'pickupRequest',
            'foodDonation.restaurant',
            'ngo',
        ])->findOrFail($id);

        $isRestaurant = ($user->role === 'restaurant' && $user->restaurant?->id === $certificate->restaurant_id);
        $isNgo = ($user->role === 'ngo' && $user->ngo?->id === $certificate->ngo_id);
        $isAdmin = ($user->role === 'admin');

        if (!$isRestaurant && !$isNgo && !$isAdmin) {
            abort(403, 'Unauthorized to download this certificate.');
        }

        return $this->certificateService->downloadPdf($certificate);
    }

    /**
     * Public Certificate Verification Page (Accessible via QR Code scan).
     */
    public function verify($token)
    {
        $certificate = DonationCertificate::with([
            'foodDonation.restaurant',
            'ngo',
            'pickupRequest.verification.volunteer',
        ])->where('verification_token', $token)->first();

        $isValid = ($certificate !== null);
        $qrCodeDataUri = $isValid ? $this->qrCodeService->generateDataUri(route('certificates.verify', ['token' => $token]), 180) : null;

        return view('certificates.public_verify', compact('certificate', 'isValid', 'qrCodeDataUri', 'token'));
    }

    /**
     * Restaurant certificates list view.
     */
    public function restaurantIndex()
    {
        $restaurant = auth()->user()->restaurant;
        if (!$restaurant) {
            return redirect()->route('restaurant.profile')->with('error', 'Please complete your restaurant profile.');
        }

        $certificates = DonationCertificate::with(['foodDonation', 'ngo', 'pickupRequest'])
            ->where('restaurant_id', $restaurant->id)
            ->latest()
            ->paginate(12);

        return view('restaurant.certificates.index', compact('certificates', 'restaurant'));
    }
}
