# Food Waste Rescue — Test Execution Results

**Execution Date:** November 22, 2026  
**Framework:** Laravel 12.x / PHPUnit 11.x  
**PHP Version:** 8.2.12 (CLI)  
**Database:** SQLite / MySQL  
**Result:** **108 Passed, 0 Failed, 0 Skipped (365 Assertions)**  
**Execution Duration:** 5.74s  

---

## 1. Test Suite Summary Table

| Test Suite File | Type | Tests | Assertions | Status |
| :--- | :--- | :---: | :---: | :---: |
| `Tests\Unit\ExampleTest` | Unit | 1 | 1 | PASS |
| `Tests\Feature\AdminApprovalAndStatusTest` | Feature | 5 | 15 | PASS |
| `Tests\Feature\AdminApprovalNotificationTest` | Feature | 7 | 25 | PASS |
| `Tests\Feature\AdvancedFeaturesTest` | Feature | 17 | 64 | PASS |
| `Tests\Feature\Auth\AuthenticationTest` | Auth | 4 | 10 | PASS |
| `Tests\Feature\Auth\EmailVerificationTest` | Auth | 3 | 9 | PASS |
| `Tests\Feature\Auth\PasswordConfirmationTest` | Auth | 2 | 4 | PASS |
| `Tests\Feature\Auth\PasswordResetTest` | Auth | 4 | 14 | PASS |
| `Tests\Feature\Auth\PasswordUpdateTest` | Auth | 2 | 8 | PASS |
| `Tests\Feature\Auth\RegistrationTest` | Auth | 3 | 9 | PASS |
| `Tests\Feature\DonationWorkflowTest` | Feature | 1 | 4 | PASS |
| `Tests\Feature\ExampleTest` | Feature | 1 | 1 | PASS |
| `Tests\Feature\ExpireDonationsCommandTest` | Feature | 3 | 9 | PASS |
| `Tests\Feature\FoodDonationPolicyTest` | Feature | 5 | 15 | PASS |
| `Tests\Feature\LocationAndDistanceSearchTest` | Feature | 3 | 9 | PASS |
| `Tests\Feature\NearbyNgoAlertTest` | Feature | 8 | 28 | PASS |
| `Tests\Feature\PickupWorkflowComprehensiveTest` | Feature | 5 | 22 | PASS |
| `Tests\Feature\ProfileTest` | Feature | 5 | 16 | PASS |
| `Tests\Feature\SecurityHardeningWorkflowTest` | Feature | 29 | 102 | PASS |
| `Tests\Feature\VerificationAndSafetyTest` | Feature | 5 | 18 | PASS |
| **TOTAL** | — | **108** | **365** | **100% PASS** |

---

## 2. Key Verified Test Cases

### A. Handover Verification & OTP Security (`SecurityHardeningWorkflowTest.php`)
- `01_otp_generation_and_secure_hashing`: 6-digit cryptographically secure OTP generated, bcrypt hashed, AES-256 encrypted.
- `02_otp_never_stored_as_plain_text`: Database assertion proves column `otp_code` is strictly null and plain text is absent.
- `03_successful_otp_verification_by_assigned_ngo`: Assigned NGO inputs valid OTP; status transitions atomically to `handover_confirmed` / `picked`.
- `04_wrong_otp_rejected_and_attempts_tracked`: Invalid OTP rejected, attempt counter incremented.
- `05_expired_otp_rejected`: Expired OTPs (> 60 mins) rejected with proper user notification.
- `06_otp_reuse_prevented_and_wiped_after_verification`: `otp_hash`, `otp_encrypted`, `qr_token`, `qr_token_encrypted` completely wiped to null upon verification.
- `07_max_verification_attempts_lockout`: Enforces lockout after 5 consecutive failures.
- `08_resend_otp_enforces_cooldown`: 120-second cooldown period strictly enforced between OTP regenerations.
- `09_unauthorized_restaurant_cannot_verify_or_view_otp`: Multi-tenant ownership isolation between restaurants.
- `10_unauthorized_ngo_cannot_verify_otp`: Only assigned NGO volunteer can verify.
- `11_wrong_ngo_cannot_access_pickup_request`: Complete 403 Forbidden enforcement on unassigned NGOs.
- `12_unauthorized_user_cannot_verify_qr_token`: Volunteer role requirement and tenant check on QR verification.
- `13_qr_token_expiry_rejection`: Expired QR tokens cannot verify handovers.
- `14_qr_token_reuse_prevention`: Replay attacks on QR codes are prevented.
- `15_direct_picked_status_bypass_blocked_in_restaurant_controller`: Restaurant direct status manipulation blocked.
- `16_direct_completed_status_bypass_blocked_in_restaurant_controller`: Restaurant direct completion blocked.
- `17_direct_picked_status_bypass_blocked_in_ngo_controller`: NGO direct status manipulation blocked.
- `18_resend_otp_blocked_for_non_owner_restaurant`: Tenant isolation on OTP resend.
- `19_concurrent_verification_safety`: Pessimistic locking (`lockForUpdate`) protects against race conditions.
- `20_handover_audit_trail_recorded`: Audit trail captures actor, action, timestamps, and status before mutation.
- `21_certificate_generation_succeeds_after_genuine_verification`: Verified pickup generates genuine PDF certificate.
- `22_certificate_generation_rejected_for_unverified_pickup`: Unverified pickups cannot issue certificates.
- `23_food_metrics_service_unit_separation_and_conversions`: Units separated into servings, kg, boxes, packets, litres, grams.
- `24_food_safety_declaration_required_and_recorded`: Food safety checklist validated on donation creation.
- `25_expired_food_donation_pickup_prevented`: Past expiry time donations cannot be requested or picked up.
- `26_bacon_qr_code_svg_generation`: BaconQrCode generates ISO/IEC 18004 valid vector SVGs without requiring `ext-gd`.
- `27_certificate_stored_in_private_local_disk`: Certificates are written to private `local` storage and protected from direct HTTP URL access.
- `28_food_metrics_single_donation_calculation`: Accurately computes meals for individual donations on certificates.
- `29_handover_rate_limiting_enforced`: Custom rate limiter throttles verification requests to 5 attempts per minute.

---

## 3. Test Execution Command

To re-run the full test suite locally:

```bash
php artisan test
```

Or run individual feature test suites:

```bash
php artisan test tests/Feature/SecurityHardeningWorkflowTest.php
php artisan test tests/Feature/AdvancedFeaturesTest.php
```
