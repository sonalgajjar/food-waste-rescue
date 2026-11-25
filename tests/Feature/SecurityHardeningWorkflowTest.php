<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Restaurant;
use App\Models\NGO;
use App\Models\FoodDonation;
use App\Models\PickupRequest;
use App\Models\PickupVerification;
use App\Models\HandoverAuditLog;
use App\Models\DonationCertificate;
use App\Services\HandoverVerificationService;
use App\Services\PickupWorkflowService;
use App\Services\CertificateService;
use App\Services\FoodMetricsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SecurityHardeningWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $restaurantUser;
    protected User $otherRestaurantUser;
    protected Restaurant $restaurant;
    protected Restaurant $otherRestaurant;

    protected User $ngoUser;
    protected User $otherNgoUser;
    protected NGO $ngo;
    protected NGO $otherNgo;

    protected User $adminUser;
    protected FoodDonation $donation;
    protected PickupRequest $pickupRequest;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Restaurant 1
        $this->restaurantUser = User::factory()->create([
            'role' => 'restaurant',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $this->restaurant = Restaurant::create([
            'user_id' => $this->restaurantUser->id,
            'restaurant_name' => 'Grand Spice Bistro',
            'owner_name' => 'Chef Vikram',
            'email' => $this->restaurantUser->email,
            'phone' => '9876543210',
            'license_number' => 'FSSAI-100200300',
            'license_expires_at' => now()->addYear(),
            'address' => 'Connaught Place',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'pincode' => '110001',
            'latitude' => 28.6315,
            'longitude' => 77.2167,
            'status' => 'active',
        ]);

        // 2. Restaurant 2 (for unauthorized test cases)
        $this->otherRestaurantUser = User::factory()->create([
            'role' => 'restaurant',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $this->otherRestaurant = Restaurant::create([
            'user_id' => $this->otherRestaurantUser->id,
            'restaurant_name' => 'Royal Heritage Cafe',
            'owner_name' => 'Rohan Sen',
            'email' => $this->otherRestaurantUser->email,
            'phone' => '9876543211',
            'license_number' => 'FSSAI-999888777',
            'license_expires_at' => now()->addYear(),
            'address' => 'Hauz Khas',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'pincode' => '110016',
            'latitude' => 28.5494,
            'longitude' => 77.2001,
            'status' => 'active',
        ]);

        // 3. NGO 1
        $this->ngoUser = User::factory()->create([
            'role' => 'ngo',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $this->ngo = NGO::create([
            'user_id' => $this->ngoUser->id,
            'ngo_name' => 'Hope Food Relief Foundation',
            'owner_name' => 'Sunita Sharma',
            'email' => $this->ngoUser->email,
            'phone' => '9123456780',
            'registration_number' => 'NGO-DEL-2024-001',
            'registration_expires_at' => now()->addYear(),
            'address' => 'Barakhamba Road',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'pincode' => '110001',
            'latitude' => 28.6290,
            'longitude' => 77.2250,
            'status' => 'active',
        ]);

        // 4. NGO 2 (for unauthorized tests)
        $this->otherNgoUser = User::factory()->create([
            'role' => 'ngo',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $this->otherNgo = NGO::create([
            'user_id' => $this->otherNgoUser->id,
            'ngo_name' => 'City Care NGO',
            'owner_name' => 'Arun Verma',
            'email' => $this->otherNgoUser->email,
            'phone' => '9123456781',
            'registration_number' => 'NGO-DEL-2024-002',
            'registration_expires_at' => now()->addYear(),
            'address' => 'Karol Bagh',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'pincode' => '110005',
            'latitude' => 28.6514,
            'longitude' => 77.1907,
            'status' => 'active',
        ]);

        // 5. Admin
        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        // 6. Food Donation
        $this->donation = FoodDonation::create([
            'restaurant_id' => $this->restaurant->id,
            'food_name' => 'Surplus Veg Pulao & Dal Makhani',
            'category' => 'Veg',
            'food_type' => 'Cooked Meals',
            'quantity' => 20,
            'unit' => 'servings',
            'cooked_time' => now()->subHour(),
            'expiry_time' => now()->addHours(6),
            'pickup_address' => 'Connaught Place, Delhi',
            'latitude' => 28.6315,
            'longitude' => 77.2167,
            'food_safety_declaration' => true,
            'safety_declared_at' => now(),
            'safety_declared_by_user_id' => $this->restaurantUser->id,
            'status' => 'approved',
        ]);

        // 7. Pickup Request
        $this->pickupRequest = PickupRequest::create([
            'food_donation_id' => $this->donation->id,
            'ngo_id' => $this->ngo->id,
            'pickup_time' => now()->addHours(2),
            'status' => 'accepted',
        ]);
    }

    /**
     * TEST 01: OTP generation and cryptographically secure hashing.
     */
    public function test_01_otp_generation_and_secure_hashing(): void
    {
        $service = app(HandoverVerificationService::class);
        $verification = $service->getOrCreateVerification($this->pickupRequest);

        $this->assertNotNull($verification);
        $this->assertEquals(6, strlen($verification->getDecryptedOtp()));
        $this->assertMatchesRegularExpression('/^[0-9]{6}$/', $verification->getDecryptedOtp());
        $this->assertNotNull($verification->otp_hash);
        $this->assertTrue(Hash::check($verification->getDecryptedOtp(), $verification->otp_hash));
    }

    /**
     * TEST 02: OTP is never stored in plain text in database.
     */
    public function test_02_otp_never_stored_as_plain_text(): void
    {
        $service = app(HandoverVerificationService::class);
        $verification = $service->getOrCreateVerification($this->pickupRequest);

        // Fetch direct raw DB row
        $rawRow = DB::table('pickup_verifications')->where('id', $verification->id)->first();
        $this->assertNull($rawRow->otp_code, 'Plain text OTP must NEVER be stored in the database');
        $this->assertNotNull($rawRow->otp_hash, 'Hashed OTP must be present');
        $this->assertNotNull($rawRow->otp_encrypted, 'Encrypted OTP must be stored for authorized donor view');
    }

    /**
     * TEST 03: Successful OTP verification by assigned NGO updates status.
     */
    public function test_03_successful_otp_verification_by_assigned_ngo(): void
    {
        $service = app(HandoverVerificationService::class);
        $verification = $service->getOrCreateVerification($this->pickupRequest);
        $otp = $verification->getDecryptedOtp();

        $response = $this->actingAs($this->ngoUser)->post(route('handover.verify.otp', $this->pickupRequest->id), [
            'otp' => $otp,
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals('handover_confirmed', $this->pickupRequest->fresh()->status);
        $this->assertEquals('picked', $this->donation->fresh()->status);
        $this->assertEquals('verified', $verification->fresh()->status);
        $this->assertEquals('otp', $verification->fresh()->verification_method);
    }

    /**
     * TEST 04: Wrong OTP rejected and attempts tracked.
     */
    public function test_04_wrong_otp_rejected_and_attempts_tracked(): void
    {
        $service = app(HandoverVerificationService::class);
        $verification = $service->getOrCreateVerification($this->pickupRequest);

        $response = $this->actingAs($this->ngoUser)->post(route('handover.verify.otp', $this->pickupRequest->id), [
            'otp' => '999999',
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals('accepted', $this->pickupRequest->fresh()->status);
        $this->assertEquals(1, $verification->fresh()->attempts);
    }

    /**
     * TEST 05: Expired OTP rejected.
     */
    public function test_05_expired_otp_rejected(): void
    {
        $service = app(HandoverVerificationService::class);
        $verification = $service->getOrCreateVerification($this->pickupRequest);
        $otp = $verification->getDecryptedOtp();

        // Expire the OTP
        $verification->update(['otp_expires_at' => now()->subMinute()]);

        $response = $this->actingAs($this->ngoUser)->post(route('handover.verify.otp', $this->pickupRequest->id), [
            'otp' => $otp,
        ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('expired', strtolower(session('error')));
        $this->assertEquals('accepted', $this->pickupRequest->fresh()->status);
    }

    /**
     * TEST 06: OTP reuse prevention and tokens wiped after verification.
     */
    public function test_06_otp_reuse_prevented_and_wiped_after_verification(): void
    {
        $service = app(HandoverVerificationService::class);
        $verification = $service->getOrCreateVerification($this->pickupRequest);
        $otp = $verification->getDecryptedOtp();

        // Verify once
        $this->actingAs($this->ngoUser)->post(route('handover.verify.otp', $this->pickupRequest->id), [
            'otp' => $otp,
        ]);

        // Tokens must be wiped to prevent replay
        $freshVer = $verification->fresh();
        $this->assertNull($freshVer->otp_encrypted, 'Encrypted OTP must be wiped after verification');
        $this->assertNull($freshVer->otp_hash, 'Hashed OTP must be wiped after verification');
        $this->assertNull($freshVer->qr_token, 'QR token must be wiped after verification');

        // Second verification attempt must fail
        $responseSecond = $this->actingAs($this->ngoUser)->post(route('handover.verify.otp', $this->pickupRequest->id), [
            'otp' => $otp,
        ]);

        $responseSecond->assertSessionHas('error');
    }

    /**
     * TEST 07: Maximum 5 attempts lockout.
     */
    public function test_07_max_verification_attempts_lockout(): void
    {
        $service = app(HandoverVerificationService::class);
        $verification = $service->getOrCreateVerification($this->pickupRequest);

        // Fail 5 times
        for ($i = 1; $i <= 5; $i++) {
            $this->actingAs($this->ngoUser)->post(route('handover.verify.otp', $this->pickupRequest->id), [
                'otp' => '000000',
            ]);
        }

        $this->assertEquals(5, $verification->fresh()->attempts);

        // 6th attempt with valid OTP should be locked out
        $otp = $verification->fresh()->getDecryptedOtp();
        $response = $this->actingAs($this->ngoUser)->post(route('handover.verify.otp', $this->pickupRequest->id), [
            'otp' => $otp ?? '123456',
        ]);

        $response->assertSessionHas('error');
        $errorMsg = strtolower((string) session('error'));
        $this->assertTrue(str_contains($errorMsg, 'maximum') || str_contains($errorMsg, 'too many'));
    }

    /**
     * TEST 08: Resend OTP enforces cooldown period.
     */
    public function test_08_resend_otp_enforces_cooldown(): void
    {
        $service = app(HandoverVerificationService::class);
        $verification = $service->getOrCreateVerification($this->pickupRequest);

        // Immediate resend should fail due to 60s cooldown
        $response = $this->actingAs($this->restaurantUser)->post(route('handover.resend.otp', $this->pickupRequest->id));
        $response->assertSessionHas('error');

        // Fast-forward time past cooldown
        $verification->update(['resend_available_at' => now()->subSecond()]);

        $responseSuccess = $this->actingAs($this->restaurantUser)->post(route('handover.resend.otp', $this->pickupRequest->id));
        $responseSuccess->assertSessionHas('success');
    }

    /**
     * TEST 09: Unauthorized restaurant cannot view OTP/QR verification data.
     */
    public function test_09_unauthorized_restaurant_cannot_verify_or_view_otp(): void
    {
        $service = app(HandoverVerificationService::class);
        $service->getOrCreateVerification($this->pickupRequest);

        // Other restaurant tries to fetch verification data
        $response = $this->actingAs($this->otherRestaurantUser)->getJson(route('handover.data', $this->pickupRequest->id));
        $response->assertStatus(403);
    }

    /**
     * TEST 10: Unauthorized NGO cannot verify OTP.
     */
    public function test_10_unauthorized_ngo_cannot_verify_otp(): void
    {
        $service = app(HandoverVerificationService::class);
        $verification = $service->getOrCreateVerification($this->pickupRequest);
        $otp = $verification->getDecryptedOtp();

        // Other NGO tries to verify OTP
        $response = $this->actingAs($this->otherNgoUser)->post(route('handover.verify.otp', $this->pickupRequest->id), [
            'otp' => $otp,
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals('accepted', $this->pickupRequest->fresh()->status);
    }

    /**
     * TEST 11: Wrong NGO cannot access or manipulate another NGO's pickup request.
     */
    public function test_11_wrong_ngo_cannot_access_pickup_request(): void
    {
        $response = $this->actingAs($this->otherNgoUser)->post(route('handover.start', $this->pickupRequest->id));
        $response->assertSessionHas('error');
        $this->assertStringContainsString('authorized', strtolower(session('error')));
        $this->assertEquals('accepted', $this->pickupRequest->fresh()->status);
    }

    /**
     * TEST 12: Unauthorized user cannot verify QR token.
     */
    public function test_12_unauthorized_user_cannot_verify_qr_token(): void
    {
        $service = app(HandoverVerificationService::class);
        $verification = $service->getOrCreateVerification($this->pickupRequest);
        $token = $verification->qr_token;

        // Unauthenticated access
        $responseUnauth = $this->get(route('handover.verify.qr', ['token' => $token]));
        $responseUnauth->assertRedirect(route('login'));

        // Wrong NGO access
        $responseWrongNgo = $this->actingAs($this->otherNgoUser)->get(route('handover.verify.qr', ['token' => $token]));
        $responseWrongNgo->assertRedirect(route('ngo.history'));
        $responseWrongNgo->assertSessionHas('error');
    }

    /**
     * TEST 13: QR token expiry rejection.
     */
    public function test_13_qr_token_expiry_rejection(): void
    {
        $service = app(HandoverVerificationService::class);
        $verification = $service->getOrCreateVerification($this->pickupRequest);
        $token = $verification->qr_token;

        // Expire token
        $verification->update(['otp_expires_at' => now()->subMinutes(5)]);

        $response = $this->actingAs($this->ngoUser)->get(route('handover.verify.qr', ['token' => $token]));
        $response->assertSessionHas('error');
        $this->assertEquals('accepted', $this->pickupRequest->fresh()->status);
    }

    /**
     * TEST 14: QR token reuse prevention.
     */
    public function test_14_qr_token_reuse_prevention(): void
    {
        $service = app(HandoverVerificationService::class);
        $verification = $service->getOrCreateVerification($this->pickupRequest);
        $token = $verification->qr_token;

        // 1st verify succeeds
        $this->actingAs($this->ngoUser)->get(route('handover.verify.qr', ['token' => $token]));
        $this->assertEquals('handover_confirmed', $this->pickupRequest->fresh()->status);

        // 2nd verify with same token must fail
        $responseSecond = $this->actingAs($this->ngoUser)->get(route('handover.verify.qr', ['token' => $token]));
        $responseSecond->assertSessionHas('error');
    }

    /**
     * TEST 15: Direct 'picked' status update bypass is blocked in RestaurantController.
     */
    public function test_15_direct_picked_status_bypass_blocked_in_restaurant_controller(): void
    {
        $response = $this->actingAs($this->restaurantUser)->post("/restaurant/requests/{$this->pickupRequest->id}/status", [
            'status' => 'picked',
        ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('forbidden', strtolower(session('error')));
        $this->assertEquals('accepted', $this->pickupRequest->fresh()->status);
    }

    /**
     * TEST 16: Direct 'completed' status update bypass is blocked in RestaurantController.
     */
    public function test_16_direct_completed_status_bypass_blocked_in_restaurant_controller(): void
    {
        $response = $this->actingAs($this->restaurantUser)->post("/restaurant/requests/{$this->pickupRequest->id}/status", [
            'status' => 'completed',
        ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('forbidden', strtolower(session('error')));
        $this->assertEquals('accepted', $this->pickupRequest->fresh()->status);
    }

    /**
     * TEST 17: Direct 'picked' status update bypass is blocked in NGOController.
     */
    public function test_17_direct_picked_status_bypass_blocked_in_ngo_controller(): void
    {
        $response = $this->actingAs($this->ngoUser)->post("/ngo/requests/{$this->pickupRequest->id}/status", [
            'status' => 'picked',
        ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('forbidden', strtolower(session('error')));
        $this->assertEquals('accepted', $this->pickupRequest->fresh()->status);
    }

    /**
     * TEST 18: Resend OTP blocked for non-owner restaurant.
     */
    public function test_18_resend_otp_blocked_for_non_owner_restaurant(): void
    {
        $service = app(HandoverVerificationService::class);
        $service->getOrCreateVerification($this->pickupRequest);

        $response = $this->actingAs($this->otherRestaurantUser)->post(route('handover.resend.otp', $this->pickupRequest->id));
        $response->assertSessionHas('error');
        $this->assertStringContainsString('unauthorized', strtolower(session('error')));
    }

    /**
     * TEST 19: Concurrent verification safety using database transactions.
     */
    public function test_19_concurrent_verification_safety(): void
    {
        $service = app(HandoverVerificationService::class);
        $verification = $service->getOrCreateVerification($this->pickupRequest);
        $otp = $verification->getDecryptedOtp();

        // Verification service uses DB transaction and updates status atomically
        $result = $service->verifyByOtp($this->pickupRequest, $otp, $this->ngoUser);
        $this->assertTrue($result['success']);

        // Subsequent call fails immediately because request is no longer in valid verification state
        $resultSecond = $service->verifyByOtp($this->pickupRequest->fresh(), $otp, $this->ngoUser);
        $this->assertFalse($resultSecond['success']);
    }

    /**
     * TEST 20: Handover audit trail is recorded.
     */
    public function test_20_handover_audit_trail_recorded(): void
    {
        $service = app(HandoverVerificationService::class);
        $verification = $service->getOrCreateVerification($this->pickupRequest);
        $otp = $verification->getDecryptedOtp();

        $this->actingAs($this->ngoUser)->post(route('handover.verify.otp', $this->pickupRequest->id), [
            'otp' => $otp,
        ]);

        $this->assertDatabaseHas('handover_audit_logs', [
            'pickup_request_id' => $this->pickupRequest->id,
            'action' => 'otp_verified',
            'verification_method' => 'otp',
            'status_after' => 'handover_confirmed',
        ]);
    }

    /**
     * TEST 21: Certificate generation succeeds after genuine verification.
     */
    public function test_21_certificate_generation_succeeds_after_genuine_verification(): void
    {
        $service = app(HandoverVerificationService::class);
        $verification = $service->getOrCreateVerification($this->pickupRequest);
        $otp = $verification->getDecryptedOtp();

        // Legitimate verification
        $this->actingAs($this->ngoUser)->post(route('handover.verify.otp', $this->pickupRequest->id), [
            'otp' => $otp,
        ]);

        // Complete handover
        $response = $this->actingAs($this->restaurantUser)->post(route('handover.complete', $this->pickupRequest->id));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('donation_certificates', [
            'pickup_request_id' => $this->pickupRequest->id,
            'restaurant_id' => $this->restaurant->id,
            'ngo_id' => $this->ngo->id,
        ]);
    }

    /**
     * TEST 22: Certificate generation is rejected for unverified pickup request.
     */
    public function test_22_certificate_generation_rejected_for_unverified_pickup(): void
    {
        $certService = app(CertificateService::class);

        // Attempting to generate certificate without verification must throw InvalidArgumentException
        $this->expectException(\InvalidArgumentException::class);
        $certService->generateForPickup($this->pickupRequest);
    }

    /**
     * TEST 23: FoodMetricsService unit separation and conversions.
     */
    public function test_23_food_metrics_service_unit_separation_and_conversions(): void
    {
        $metricsService = app(FoodMetricsService::class);

        // Create completed donations with different units
        FoodDonation::create([
            'restaurant_id' => $this->restaurant->id,
            'food_name' => 'Rice Trays',
            'category' => 'Veg',
            'food_type' => 'Cooked Meals',
            'quantity' => 10,
            'unit' => 'kg',
            'cooked_time' => now()->subHours(2),
            'expiry_time' => now()->addHours(4),
            'pickup_address' => 'Test',
            'status' => 'completed',
        ]);

        FoodDonation::create([
            'restaurant_id' => $this->restaurant->id,
            'food_name' => 'Meals',
            'category' => 'Veg',
            'food_type' => 'Cooked Meals',
            'quantity' => 50,
            'unit' => 'servings',
            'cooked_time' => now()->subHours(2),
            'expiry_time' => now()->addHours(4),
            'pickup_address' => 'Test',
            'status' => 'completed',
        ]);

        $metrics = $metricsService->calculateMetrics();

        // Assert units are tracked separately
        $this->assertEquals(10.0, $metrics['by_unit']['kg']);
        $this->assertEquals(50.0, $metrics['by_unit']['servings']);

        // Check estimated meals conversion: 10 kg * 2.5 meals/kg + 50 servings = 75 meals
        $this->assertEquals(75, $metrics['estimated_meals']);

        // Check CO2 emissions diverted: 10 kg * 2.5 kg CO2/kg + 50 servings * 1.25 = 25 + 62.5 = 87.5 kg
        $this->assertEquals(87.5, $metrics['co2_saved_kg']);
    }

    /**
     * TEST 24: Food safety declaration is required and audited on creation.
     */
    public function test_24_food_safety_declaration_required_and_recorded(): void
    {
        // 1. Missing declaration fails validation
        $responseFailed = $this->actingAs($this->restaurantUser)->post(route('restaurant.donations.store'), [
            'food_name' => 'Tandoori Roti',
            'category' => 'Veg',
            'food_type' => 'Cooked Meals',
            'quantity' => 30,
            'unit' => 'servings',
            'cooked_time' => now()->subHour()->format('Y-m-d\TH:i'),
            'expiry_time' => now()->addHours(4)->format('Y-m-d\TH:i'),
            'pickup_address' => 'Connaught Place',
            'latitude' => 28.6315,
            'longitude' => 77.2167,
            // Missing food_safety_declaration
        ]);
        $responseFailed->assertSessionHasErrors('food_safety_declaration');

        // 2. Providing declaration succeeds and saves audit timestamps
        $responseSuccess = $this->actingAs($this->restaurantUser)->post(route('restaurant.donations.store'), [
            'food_name' => 'Tandoori Roti',
            'category' => 'Veg',
            'food_type' => 'Cooked Meals',
            'quantity' => 30,
            'unit' => 'servings',
            'cooked_time' => now()->subHour()->format('Y-m-d\TH:i'),
            'expiry_time' => now()->addHours(4)->format('Y-m-d\TH:i'),
            'pickup_address' => 'Connaught Place',
            'latitude' => 28.6315,
            'longitude' => 77.2167,
            'food_safety_declaration' => 1,
        ]);
        $responseSuccess->assertRedirect(route('restaurant.donations.index'));

        $savedDonation = FoodDonation::where('food_name', 'Tandoori Roti')->first();
        $this->assertNotNull($savedDonation);
        $this->assertTrue((bool) $savedDonation->food_safety_declaration);
        $this->assertNotNull($savedDonation->safety_declared_at);
        $this->assertEquals($this->restaurantUser->id, $savedDonation->safety_declared_by_user_id);
    }

    /**
     * TEST 25: Expired food donation pickup request is prevented.
     */
    public function test_25_expired_food_donation_pickup_prevented(): void
    {
        $expiredDonation = FoodDonation::create([
            'restaurant_id' => $this->restaurant->id,
            'food_name' => 'Expired Curry',
            'category' => 'Veg',
            'food_type' => 'Cooked Meals',
            'quantity' => 5,
            'unit' => 'servings',
            'cooked_time' => now()->subHours(8),
            'expiry_time' => now()->subHour(), // Expired
            'pickup_address' => 'Connaught Place',
            'food_safety_declaration' => true,
            'status' => 'available',
        ]);

        $response = $this->actingAs($this->ngoUser)->post(route('ngo.requests.store', $expiredDonation->id), [
            'pickup_time' => now()->addHour()->format('Y-m-d\TH:i'),
            'remarks' => 'Urgent pickup',
        ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('expired', strtolower(session('error')));
    }

    /**
     * TEST 26: BaconQrCode ISO/IEC 18004 SVG vector generation.
     */
    public function test_26_bacon_qr_code_svg_generation(): void
    {
        $qrService = app(\App\Services\QRCodeService::class);
        $testUrl = 'https://example.com/handover/verify-qr/token-xyz-123456';
        
        $svg = $qrService->generateSvg($testUrl, 250);
        $this->assertStringContainsString('<svg', $svg);
        $this->assertStringContainsString('viewBox=', $svg);
        $this->assertStringContainsString('</svg>', $svg);

        $dataUri = $qrService->generateDataUri($testUrl, 200);
        $this->assertStringStartsWith('data:image/svg+xml;base64,', $dataUri);
    }

    /**
     * TEST 27: Certificate is stored in private local disk, not public disk.
     */
    public function test_27_certificate_stored_in_private_local_disk(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        \Illuminate\Support\Facades\Storage::fake('public');

        $service = app(HandoverVerificationService::class);
        $workflow = app(\App\Services\PickupWorkflowService::class);
        $verification = $service->getOrCreateVerification($this->pickupRequest);
        $otp = $verification->getDecryptedOtp();

        // Verify handover
        $service->verifyByOtp($this->pickupRequest, $otp, $this->ngoUser);

        // Complete pickup
        $certificate = $workflow->completePickup($this->pickupRequest->fresh(), $this->restaurantUser);

        // Assert file exists on private 'local' disk and NOT on 'public' disk
        $this->assertTrue(\Illuminate\Support\Facades\Storage::disk('local')->exists($certificate->pdf_path));
        $this->assertFalse(\Illuminate\Support\Facades\Storage::disk('public')->exists($certificate->pdf_path));
    }

    /**
     * TEST 28: FoodMetrics single donation calculation handles various units accurately.
     */
    public function test_28_food_metrics_single_donation_calculation(): void
    {
        $metricsService = app(\App\Services\FoodMetricsService::class);

        // 10 kg -> 25 meals
        $donationKg = new FoodDonation(['quantity' => 10, 'unit' => 'kg']);
        $this->assertEquals(25, $metricsService->calculateDonationMeals($donationKg));

        // 40 servings -> 40 meals
        $donationServings = new FoodDonation(['quantity' => 40, 'unit' => 'servings']);
        $this->assertEquals(40, $metricsService->calculateDonationMeals($donationServings));

        // 15 boxes -> 15 meals
        $donationBoxes = new FoodDonation(['quantity' => 15, 'unit' => 'boxes']);
        $this->assertEquals(15, $metricsService->calculateDonationMeals($donationBoxes));

        // 2000 grams -> 5 meals
        $donationGrams = new FoodDonation(['quantity' => 2000, 'unit' => 'grams']);
        $this->assertEquals(5, $metricsService->calculateDonationMeals($donationGrams));

        // 4 litres -> 10 meals
        $donationLitres = new FoodDonation(['quantity' => 4, 'unit' => 'litres']);
        $this->assertEquals(10, $metricsService->calculateDonationMeals($donationLitres));
    }

    /**
     * TEST 29: Handover rate limiting protects endpoints against excessive requests.
     */
    public function test_29_handover_rate_limiting_enforced(): void
    {
        // otp-verify rate limit is 5 per minute
        // First 5 requests reach application
        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($this->ngoUser)->post(route('handover.verify.otp', $this->pickupRequest->id), [
                'otp' => '999999',
            ]);
        }

        // 6th request is throttled
        $response = $this->actingAs($this->ngoUser)->post(route('handover.verify.otp', $this->pickupRequest->id), [
            'otp' => '999999',
        ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('too many', strtolower(session('error')));
    }
}
