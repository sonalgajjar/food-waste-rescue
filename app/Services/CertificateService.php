<?php

namespace App\Services;

use App\Models\DonationCertificate;
use App\Models\PickupRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CertificateService
{
    public function __construct(
        protected QRCodeService $qrCodeService,
        protected FoodMetricsService $foodMetricsService
    ) {}

    /**
     * Generate or fetch the certificate for a completed pickup request.
     */
    public function generateForPickup(PickupRequest $pickupRequest): DonationCertificate
    {
        $pickupRequest->loadMissing([
            'foodDonation.restaurant.user',
            'ngo.user',
            'verification.volunteer',
        ]);

        // 1. Must be in verified handover status
        if (!in_array($pickupRequest->status, ['handover_confirmed', 'completed'])) {
            throw new \InvalidArgumentException("Cannot generate certificate for pickup request in status '{$pickupRequest->status}'. Handover must be verified first.");
        }

        // 2. Donation and pickup request relationship validation
        if (!$pickupRequest->foodDonation || !$pickupRequest->foodDonation->restaurant_id) {
            throw new \InvalidArgumentException("Invalid pickup request: missing associated food donation or restaurant record.");
        }

        // 3. Must have a genuinely verified handover record in pickup_verifications
        $verification = $pickupRequest->verification ?? \App\Models\PickupVerification::where('pickup_request_id', $pickupRequest->id)->latest()->first();
        if (!$verification || $verification->status !== 'verified' || !$verification->verified_at) {
            throw new \InvalidArgumentException("Cannot generate certificate without a verified handover record.");
        }

        $existing = DonationCertificate::where('pickup_request_id', $pickupRequest->id)->first();
        if ($existing) {
            return $existing;
        }

        $now = Carbon::now();
        $certNumber = 'CERT-' . $now->format('Y') . '-FWR-' . strtoupper(Str::random(8));
        $verificationToken = Str::random(48);

        $mealsCount = (float) $this->foodMetricsService->calculateDonationMeals($pickupRequest->foodDonation);

        $certificate = DonationCertificate::create([
            'certificate_number' => $certNumber,
            'verification_token' => $verificationToken,
            'pickup_request_id' => $pickupRequest->id,
            'food_donation_id' => $pickupRequest->food_donation_id,
            'restaurant_id' => $pickupRequest->foodDonation->restaurant_id,
            'ngo_id' => $pickupRequest->ngo_id,
            'meals_count' => $mealsCount,
            'issued_at' => $now,
            'pdf_path' => null,
        ]);

        // Generate and store PDF file
        $pdfPath = $this->renderAndSavePdf($certificate);
        $certificate->update(['pdf_path' => $pdfPath]);

        return $certificate;
    }

    /**
     * Render certificate PDF and store it in private local storage.
     */
    public function renderAndSavePdf(DonationCertificate $certificate): string
    {
        $certificate->loadMissing([
            'pickupRequest.verification.volunteer',
            'foodDonation',
            'restaurant',
            'ngo',
        ]);

        $verifyUrl = route('certificates.verify', ['token' => $certificate->verification_token]);
        $qrCodeDataUri = $this->qrCodeService->generateDataUri($verifyUrl, 130);

        $pdf = Pdf::loadView('certificates.pdf', [
            'certificate' => $certificate,
            'donation' => $certificate->foodDonation,
            'restaurant' => $certificate->restaurant,
            'ngo' => $certificate->ngo,
            'verification' => $certificate->pickupRequest?->verification,
            'qrCodeDataUri' => $qrCodeDataUri,
            'verifyUrl' => $verifyUrl,
        ]);

        $pdf->setPaper('A4', 'portrait');

        $fileName = 'certificates/' . $certificate->certificate_number . '.pdf';
        Storage::disk('local')->put($fileName, $pdf->output());

        return $fileName;
    }

    /**
     * Download certificate PDF.
     */
    public function downloadPdf(DonationCertificate $certificate)
    {
        if (!$certificate->pdf_path || !Storage::disk('local')->exists($certificate->pdf_path)) {
            $pdfPath = $this->renderAndSavePdf($certificate);
            $certificate->update(['pdf_path' => $pdfPath]);
        }

        return Storage::disk('local')->download(
            $certificate->pdf_path,
            $certificate->certificate_number . '.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }
}
