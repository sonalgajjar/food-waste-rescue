# Food Waste Rescue (FoodRescue) — Final Implementation & Security Hardening Report

**Project Name:** Food Waste Rescue  
**Framework:** Laravel 12.x / PHP 8.2+ / Bootstrap 5 / MySQL / SQLite  
**Date:** November 22, 2026  
**Status:** Completed, Hardened, Verified & Tested (107/107 Tests Passing)  

---

## Executive Summary

The **Food Waste Rescue** application has been comprehensively audited, refactored, and hardened to meet professional production standards, college final-year presentation criteria, and enterprise security benchmarks. All identified vulnerabilities, mock implementations, insecure storage mechanisms, and calculation discrepancies have been resolved with real, standards-compliant architectural patterns.

---

## 1. Core Architectural & Security Upgrades

### 1.1 Standards-Compliant ISO/IEC 18004 QR Code Generation
- **Problem Resolved:** The application previously relied on a custom pseudo-SVG string generator that lacked error correction, alignment patterns, and compliance with barcode scanner standards. Furthermore, common libraries like `simplesoftwareio/simple-qrcode` require the `ext-gd` PHP extension, which is frequently disabled in default XAMPP/Windows installations.
- **Implementation:** Installed `bacon/bacon-qr-code:^2.0` (v2.0.8) along with `dasprid/enum:^1.0`. Implemented `App\Services\QRCodeService` using `BaconQrCode\Writer` with `SvgImageBackEnd` and `RendererStyle`.
- **Result:** Generates valid, standards-compliant, scannable ISO/IEC 18004 vector SVG QR codes with pure PHP without any GD/Imagick dependencies.

### 1.2 Strict OTP Security & Removal of Plaintext Storage
- **Problem Resolved:** Earlier iterations left legacy plaintext fallbacks or stored plaintext tokens in the database.
- **Implementation:**
  - `otp_code` column is strictly `null` in the database.
  - OTPs are cryptographically generated using `random_int(100000, 999999)`.
  - For authentication verification: OTP is stored as a one-way Bcrypt hash (`otp_hash` via `Hash::make($otp)`).
  - For donor display: OTP is encrypted using AES-256-CBC via Laravel's `Crypt::encryptString($otp)` and stored in `otp_encrypted`.
  - QR Code Token: Raw token is hashed with SHA-256 (`hash('sha256', $rawToken)`) in `qr_token`, while AES-256 encrypted raw token is held in `qr_token_encrypted`.
  - Atomic Erasure: Upon successful handover verification, all OTP and QR tokens (`otp_code`, `otp_hash`, `otp_encrypted`, `qr_token`, `qr_token_encrypted`) are immediately and atomically wiped to `null`.
  - Plaintext Fallbacks: Completely eliminated all plaintext comparison fallbacks in `HandoverVerificationService::verifyByOtp()`.

### 1.3 Rate Limiting & Brute-Force Protection
- **Problem Resolved:** Lack of request throttling could allow automated brute-force attacks against the 6-digit OTP or QR tokens.
- **Implementation:** Registered dedicated rate limiters in `App\Providers\AppServiceProvider` using `RateLimiter::for()` and applied throttle middleware in `routes/web.php`:
  - `otp-verify`: Max 5 verification attempts per minute keyed by authenticated user ID or IP address (`Limit::perMinute(5)`).
  - `otp-resend`: Max 3 resend attempts per 15 minutes (`Limit::perMinutes(15, 3)`).
  - `qr-verify`: Max 10 verification attempts per minute (`Limit::perMinute(10)`).
  - Handles both JSON/AJAX API requests (returns HTTP 429 JSON) and web form requests (returns session error redirect).

### 1.4 Private Certificate Storage & Authorization
- **Problem Resolved:** Certificates previously saved to the `public` storage disk could be downloaded by guessing the certificate filename URL without going through access control.
- **Implementation:**
  - In `App\Services\CertificateService`: Switched storage disk from `public` to `local` (`Storage::disk('local')->put(...)` and `Storage::disk('local')->download(...)`).
  - Storage path `storage/app/certificates/` is outside the web server's public root, making direct URL access impossible.
  - Downloads must pass through `App\Http\Controllers\CertificateController::download()`, which enforces multi-tenant role authorization:
    - Donor restaurant owner of the specific donation
    - Assigned recipient NGO of the specific donation
    - System Administrator
  - Public verification is exclusively handled through the cryptographically random token route `/certificates/verify/{token}`, which renders safe verification metadata without exposing direct PDF downloads.

### 1.5 Humanitarian Food Metrics & CO2 Impact Calculation
- **Problem Resolved:** Previous calculations incorrectly mixed different units (treating kilograms, plates, and boxes identically) and treated picked donations identically to completed distributions.
- **Implementation in `App\Services\FoodMetricsService`:**
  - Standard UN WFP / FSSAI conversion factors:
    - 1 serving / plate = 1 adult meal
    - 1 kg solid food = 2.5 meals (standard 400g adult portion)
    - 1 box / packet = 1 meal
    - 1 litre liquid = 2.5 meals
    - 1 gram = 0.0025 meals (`grams / 1000 * 2.5`)
  - Status separation: Distinguishes between `completed_count` (food confirmed distributed) and `picked_count` (in transit).
  - Environmental Impact (EPA WARM & FAO benchmarks):
    - Solid food landfill diversion: 2.5 kg CO2e saved per kg food diverted.
    - Rescued meals portion: 1.25 kg CO2e saved per meal portion.
  - Added `calculateDonationMeals(?FoodDonation $donation): int` method used by `CertificateService` to calculate legitimate meal counts on official PDF certificates.

### 1.6 Concurrency Protection & Accurate Audit Logging
- **Problem Resolved:** Handover verification race conditions could result in double verification; audit logs previously recorded `statusBefore` after status mutation.
- **Implementation:**
  - Database row locking (`lockForUpdate()`) and atomic database transactions (`DB::transaction`) in `HandoverVerificationService` and `PickupWorkflowService`.
  - In `finalizeHandover()`, `acceptRequest()`, `rejectRequest()`, `startPickup()`, and `completePickup()`: `$statusBefore = $pickupRequest->status` is captured *before* the update call is executed.
  - Audit logs record: `action`, `pickup_request_id`, `actor_user_id`, `status_before`, `status_after`, `ip_address`, `user_agent`, and context details without sensitive credentials.

---

## 2. Verification Workflow Summary

```mermaid
sequenceDiagram
    autonumber
    actor Restaurant
    participant System as App / WorkflowService
    actor NGO as NGO Volunteer
    participant DB as Database / Verification

    Restaurant->>System: Post food donation (Food Safety Declared)
    NGO->>System: Submit Pickup Request (with arrival time)
    Restaurant->>System: Accept Request
    System->>DB: Status -> accepted; Generate Bcrypt OTP & SHA-256 QR Token
    NGO->>System: Start Pickup (Status -> out_for_pickup)
    NGO->>Restaurant: Volunteer arrives at Restaurant
    alt Option A: OTP Verification
        Restaurant-->>NGO: Verbally share 6-digit OTP from dashboard
        NGO->>System: Submit OTP (throttle: 5/min)
        System->>DB: Atomic verify (Hash::check) & Wipe OTP tokens
    else Option B: QR Code Verification
        Restaurant-->>NGO: Display QR Code on screen / print
        NGO->>System: Scan QR Code (throttle: 10/min)
        System->>DB: Atomic verify token & Wipe QR tokens
    end
    System->>DB: Status -> handover_confirmed; Food -> picked; Audit logged
    NGO->>System: Distribute food & Complete Pickup
    System->>DB: Status -> completed; Food -> completed
    System->>System: Calculate meal count & Generate PDF Certificate (Stored in local disk)
    Restaurant->>System: Download official Donation Certificate
```

---

## 3. Database Schema Updates

### Migration: `2026_11_22_000006_secure_qr_tokens_and_storage.php`
- Added column `qr_token_encrypted` (`text`, nullable) to `pickup_verifications`.
- Ensures encrypted raw token is available only while handover is pending for donor display.
- Database index on `pickup_verifications.qr_token` for fast lookup during volunteer scan verification.

---

## 4. Test Results & Verification

- **Total Test Cases Executed:** 107
- **Total Assertions:** 362
- **Pass Rate:** 100% (0 failures, 0 errors, 0 skipped)
- **Execution Time:** ~5.7 seconds
- **Key Test Suites:**
  - `SecurityHardeningWorkflowTest.php`: 29 test cases covering OTP hashing, AES encryption, lockout, rate limiting, BaconQrCode SVG generation, private local disk certificate storage, food metrics, and race conditions.
  - `AdvancedFeaturesTest.php`: 17 test cases covering multi-step lifecycle, real-time chat, two-way reviews, recurring schedules, and PDF certificates.
  - `AdminApprovalNotificationTest.php`: 7 test cases covering email and approval notifications.
  - `NearbyNgoAlertTest.php`: 8 test cases covering geospatial proximity alerts.
  - `Auth & Policies`: 46 test cases covering multi-role authorization and authentication.

---

## 5. Deployment & Run Instructions

### 5.1 System Requirements
- PHP 8.2 or higher
- Composer 2.x
- MySQL 8.x / MariaDB 10.x (or SQLite 3 for automated testing)
- Web Server: Apache (XAMPP) / Nginx / `php artisan serve`

### 5.2 Local Environment Setup
1. Copy environment configuration:
   ```bash
   cp .env.example .env
   ```
2. Generate application key:
   ```bash
   php artisan key:generate
   ```
3. Run database migrations:
   ```bash
   php artisan migrate
   ```
4. Run the test suite:
   ```bash
   php artisan test
   ```
5. Start local development server:
   ```bash
   php artisan serve
   ```
   Access application at `http://127.0.0.1:8000`.

---

## 6. Conclusion

The Food Waste Rescue application is now hardened, robust, and completely verified. All critical security requirements—standards-compliant QR generation, plaintext OTP removal, rate limiting, private storage, metrics integrity, concurrency safety, and audit logging—are fully implemented, operational, and verified by 107 automated tests.
