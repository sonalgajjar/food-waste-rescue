<?php

namespace Tests\Feature;

use App\Models\DonationCertificate;
use App\Models\DonationReview;
use App\Models\FoodDonation;
use App\Models\NGO;
use App\Models\PickupMessage;
use App\Models\PickupRequest;
use App\Models\PickupVerification;
use App\Models\RecurringDonation;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\CertificateService;
use App\Services\HandoverVerificationService;
use App\Services\RecurringDonationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvancedFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected User $restaurantUser;
    protected Restaurant $restaurant;
    protected User $ngoUser;
    protected NGO $ngo;
    protected User $otherNgoUser;
    protected NGO $otherNgo;
    protected FoodDonation $donation;
    protected PickupRequest $pickupRequest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->restaurantUser = User::factory()->create([
            'role' => 'restaurant',
            'status' => 'active',
            'name' => 'Royal Diner Host',
            'email' => 'royal@diner.com',
        ]);

        $this->restaurant = Restaurant::create([
            'user_id' => $this->restaurantUser->id,
            'restaurant_name' => 'Royal Diner',
            'owner_name' => 'Royal Host',
            'license_number' => 'FSSAI-ROYAL-100',
            'phone' => '9888111222',
            'email' => 'royal@diner.com',
            'address' => 'Connaught Place',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'pincode' => '110001',
            'latitude' => 28.6315,
            'longitude' => 77.2167,
            'status' => 'active',
        ]);

        $this->ngoUser = User::factory()->create([
            'role' => 'ngo',
            'status' => 'active',
            'name' => 'Seva Volunteer',
            'email' => 'seva@ngo.org',
        ]);

        $this->ngo = NGO::create([
            'user_id' => $this->ngoUser->id,
            'ngo_name' => 'Seva Relief Trust',
            'registration_number' => 'NGO-SRT-555',
            'owner_name' => 'Seva Leader',
            'phone' => '9777222333',
            'email' => 'seva@ngo.org',
            'address' => 'Paharganj',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'pincode' => '110055',
            'latitude' => 28.6400,
            'longitude' => 77.2100,
            'status' => 'active',
        ]);

        // Second NGO for authorization isolation tests
        $this->otherNgoUser = User::factory()->create(['role' => 'ngo', 'status' => 'active']);
        $this->otherNgo = NGO::create([
            'user_id' => $this->otherNgoUser->id,
            'ngo_name' => 'Unrelated Care',
            'registration_number' => 'NGO-UC-999',
            'owner_name' => 'Unrelated Leader',
            'phone' => '9666333444',
            'email' => 'care@unrelated.org',
            'address' => 'Karol Bagh',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'pincode' => '110005',
            'status' => 'active',
        ]);

        $this->donation = FoodDonation::create([
            'restaurant_id' => $this->restaurant->id,
            'food_name' => 'Dal Makhani & Naan',
            'category' => 'Veg',
            'food_type' => 'Cooked Meals',
            'quantity' => 30,
            'unit' => 'servings',
            'cooked_time' => now()->subHour(),
            'expiry_time' => now()->addHours(4),
            'pickup_address' => 'Connaught Place, Delhi',
            'latitude' => 28.6315,
            'longitude' => 77.2167,
            'status' => 'requested',
        ]);

        $this->pickupRequest = PickupRequest::create([
            'food_donation_id' => $this->donation->id,
            'ngo_id' => $this->ngo->id,
            'pickup_time' => now()->addHours(2),
            'status' => 'accepted',
        ]);
    }

    // =========================================================================
    // FEATURE 1: OTP & QR CODE HANDOVER VERIFICATION TESTS
    // =========================================================================

    public function test_handover_otp_and_qr_token_are_generated(): void
    {
        $service = app(HandoverVerificationService::class);
        $verification = $service->getOrCreateVerification($this->pickupRequest);

        $this->assertNotNull($verification->getDecryptedOtp());
        $this->assertEquals(6, strlen($verification->getDecryptedOtp()));
        $this->assertNull($verification->otp_code); // plain text OTP is never stored in DB
        $this->assertNotNull($verification->otp_hash);
        $this->assertNotNull($verification->qr_token);
        $this->assertEquals(64, strlen($verification->qr_token));
        $this->assertEquals('pending', $verification->status);

        $response = $this->actingAs($this->restaurantUser)->getJson(route('handover.data', $this->pickupRequest->id));
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'otp_code',
            'qr_data_uri',
            'expires_at',
            'seconds_remaining',
        ]);
    }

    public function test_handover_can_be_verified_via_valid_otp_and_transitions_status(): void
    {
        $service = app(HandoverVerificationService::class);
        $verification = $service->getOrCreateVerification($this->pickupRequest);
        $otp = $verification->getDecryptedOtp();

        // NGO Volunteer submits the OTP
        $response = $this->actingAs($this->ngoUser)->post(route('handover.verify.otp', $this->pickupRequest->id), [
            'otp' => $otp,
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals('handover_confirmed', $this->pickupRequest->fresh()->status);
        $this->assertEquals('picked', $this->donation->fresh()->status);
        $this->assertTrue($verification->fresh()->isVerified());
        $this->assertEquals('otp', $verification->fresh()->verification_method);
    }

    public function test_handover_rejects_invalid_otp_and_tracks_attempts(): void
    {
        $service = app(HandoverVerificationService::class);
        $verification = $service->getOrCreateVerification($this->pickupRequest);

        $response = $this->actingAs($this->ngoUser)->post(route('handover.verify.otp', $this->pickupRequest->id), [
            'otp' => '000000', // Incorrect code
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals('accepted', $this->pickupRequest->fresh()->status);
        $this->assertEquals(1, $verification->fresh()->attempts);
    }

    public function test_unauthorized_volunteer_cannot_verify_another_ngos_pickup(): void
    {
        $service = app(HandoverVerificationService::class);
        $verification = $service->getOrCreateVerification($this->pickupRequest);
        $otp = $verification->getDecryptedOtp();

        // Attempt from other NGO
        $response = $this->actingAs($this->otherNgoUser)->post(route('handover.verify.otp', $this->pickupRequest->id), [
            'otp' => $otp,
        ]);

        $response->assertSessionHas('error');
        $this->assertFalse($verification->fresh()->isVerified());
    }

    public function test_handover_can_be_verified_via_qr_token(): void
    {
        $service = app(HandoverVerificationService::class);
        $verification = $service->getOrCreateVerification($this->pickupRequest);

        $response = $this->actingAs($this->ngoUser)->get(route('handover.verify.qr', ['token' => $verification->qr_token]));

        $response->assertRedirect(route('ngo.history'));
        $this->assertEquals('handover_confirmed', $this->pickupRequest->fresh()->status);
        $this->assertEquals('qr', $verification->fresh()->verification_method);
    }

    public function test_resend_otp_enforces_cooldown(): void
    {
        $service = app(HandoverVerificationService::class);
        $verification = $service->getOrCreateVerification($this->pickupRequest);

        // Try to resend immediately (should hit cooldown)
        $response = $this->actingAs($this->restaurantUser)->post(route('handover.resend.otp', $this->pickupRequest->id));
        $response->assertSessionHas('error');

        // Fast-forward cooldown
        $verification->update(['resend_available_at' => now()->subSecond()]);

        $response = $this->actingAs($this->restaurantUser)->post(route('handover.resend.otp', $this->pickupRequest->id));
        $response->assertSessionHas('success');
    }

    // =========================================================================
    // FEATURE 2: PICKUP COORDINATION REAL-TIME CHAT TESTS
    // =========================================================================

    public function test_participants_can_send_and_receive_chat_messages(): void
    {
        // 1. Volunteer sends message
        $response = $this->actingAs($this->ngoUser)->postJson(route('chat.send', $this->pickupRequest->id), [
            'message' => 'Volunteer standing at the back gate.',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('pickup_messages', [
            'pickup_request_id' => $this->pickupRequest->id,
            'user_id' => $this->ngoUser->id,
            'message' => 'Volunteer standing at the back gate.',
        ]);

        // 2. Restaurant sends reply
        $response2 = $this->actingAs($this->restaurantUser)->postJson(route('chat.send', $this->pickupRequest->id), [
            'message' => 'Food packages are ready. Chef is coming out.',
        ]);

        $response2->assertStatus(200);
        $this->assertEquals(2, PickupMessage::where('pickup_request_id', $this->pickupRequest->id)->count());
    }

    public function test_unauthorized_user_cannot_access_or_send_chat_messages(): void
    {
        $response = $this->actingAs($this->otherNgoUser)->get(route('chat.show', $this->pickupRequest->id));
        $response->assertStatus(403);

        $responsePost = $this->actingAs($this->otherNgoUser)->postJson(route('chat.send', $this->pickupRequest->id), [
            'message' => 'Intruder message',
        ]);
        $responsePost->assertStatus(403);
    }

    public function test_chat_polling_returns_new_messages_and_unread_state(): void
    {
        PickupMessage::create([
            'pickup_request_id' => $this->pickupRequest->id,
            'user_id' => $this->restaurantUser->id,
            'message' => 'Boxes sealed with heat tape.',
            'is_read' => false,
        ]);

        // Polling as NGO
        $response = $this->actingAs($this->ngoUser)->getJson(route('chat.poll', $this->pickupRequest->id) . '?last_id=0');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'messages');
        $this->assertDatabaseHas('pickup_messages', [
            'pickup_request_id' => $this->pickupRequest->id,
            'is_read' => true, // marked as read on poll
        ]);
    }

    public function test_sending_messages_is_blocked_once_pickup_is_closed(): void
    {
        $this->pickupRequest->update(['status' => 'completed']);

        $response = $this->actingAs($this->ngoUser)->postJson(route('chat.send', $this->pickupRequest->id), [
            'message' => 'Trying to message closed request',
        ]);

        $response->assertStatus(422);
    }

    // =========================================================================
    // FEATURE 3: DONATION CERTIFICATE TESTS
    // =========================================================================

    public function test_certificate_is_automatically_generated_upon_completion(): void
    {
        \App\Models\PickupVerification::create([
            'pickup_request_id' => $this->pickupRequest->id,
            'food_donation_id' => $this->pickupRequest->food_donation_id,
            'restaurant_id' => $this->restaurant->id,
            'ngo_id' => $this->ngo->id,
            'volunteer_id' => $this->ngoUser->id,
            'status' => 'verified',
            'verified_at' => now(),
            'verification_method' => 'otp',
            'otp_expires_at' => now()->addMinutes(10),
        ]);
        $this->pickupRequest->update(['status' => 'handover_confirmed']);

        $response = $this->actingAs($this->restaurantUser)->post(route('handover.complete', $this->pickupRequest->id));
        $response->assertSessionHas('success');

        $this->assertEquals('completed', $this->pickupRequest->fresh()->status);
        $this->assertDatabaseHas('donation_certificates', [
            'pickup_request_id' => $this->pickupRequest->id,
            'restaurant_id' => $this->restaurant->id,
            'ngo_id' => $this->ngo->id,
        ]);

        $cert = DonationCertificate::where('pickup_request_id', $this->pickupRequest->id)->first();
        $this->assertStringStartsWith('CERT-', $cert->certificate_number);
    }

    public function test_public_verification_page_verifies_valid_and_invalid_tokens(): void
    {
        \App\Models\PickupVerification::create([
            'pickup_request_id' => $this->pickupRequest->id,
            'food_donation_id' => $this->pickupRequest->food_donation_id,
            'restaurant_id' => $this->restaurant->id,
            'ngo_id' => $this->ngo->id,
            'volunteer_id' => $this->ngoUser->id,
            'status' => 'verified',
            'verified_at' => now(),
            'verification_method' => 'otp',
            'otp_expires_at' => now()->addMinutes(10),
        ]);
        $this->pickupRequest->update(['status' => 'handover_confirmed']);

        $certService = app(CertificateService::class);
        $cert = $certService->generateForPickup($this->pickupRequest);

        // Valid Token
        $responseValid = $this->get(route('certificates.verify', $cert->verification_token));
        $responseValid->assertStatus(200);
        $responseValid->assertSee('Authentic Donation Certificate');
        $responseValid->assertSee($cert->certificate_number);

        // Invalid Token
        $responseInvalid = $this->get(route('certificates.verify', 'INVALID-FAKE-TOKEN-999'));
        $responseInvalid->assertStatus(200);
        $responseInvalid->assertSee('Certificate Not Found');
    }

    // =========================================================================
    // FEATURE 4: TWO-WAY RATINGS AND REVIEWS TESTS
    // =========================================================================

    public function test_two_way_ratings_and_prevent_duplicate_reviews(): void
    {
        $this->pickupRequest->update(['status' => 'completed']);

        // 1. Restaurant rates NGO
        $responseRest = $this->actingAs($this->restaurantUser)->post(route('reviews.store', $this->pickupRequest->id), [
            'rating' => 5,
            'criterion_1' => 5,
            'criterion_2' => 5,
            'criterion_3' => 5,
            'review' => 'Punctual volunteer with clean insulated containers.',
        ]);
        $responseRest->assertSessionHas('success');

        $this->assertDatabaseHas('donation_reviews', [
            'pickup_request_id' => $this->pickupRequest->id,
            'reviewer_role' => 'restaurant',
            'rating' => 5,
        ]);

        // 2. Duplicate from restaurant rejected
        $responseDup = $this->actingAs($this->restaurantUser)->post(route('reviews.store', $this->pickupRequest->id), [
            'rating' => 4,
        ]);
        $responseDup->assertSessionHas('error');

        // 3. NGO rates Restaurant
        $responseNgo = $this->actingAs($this->ngoUser)->post(route('reviews.store', $this->pickupRequest->id), [
            'rating' => 5,
            'criterion_1' => 5,
            'criterion_2' => 5,
            'criterion_3' => 5,
            'review' => 'Warm, hygienic, freshly sealed food.',
        ]);
        $responseNgo->assertSessionHas('success');

        $this->assertDatabaseHas('donation_reviews', [
            'pickup_request_id' => $this->pickupRequest->id,
            'reviewer_role' => 'ngo',
            'rating' => 5,
        ]);
    }

    // =========================================================================
    // FEATURE 5: VISUAL PROGRESS STEPPER & STAGES TESTS
    // =========================================================================

    public function test_status_stepper_advances_through_lifecycle_stages(): void
    {
        // 1. NGO Starts Pickup Journey
        $responseStart = $this->actingAs($this->ngoUser)->post(route('handover.start', $this->pickupRequest->id));
        $responseStart->assertSessionHas('success');
        $this->assertEquals('out_for_pickup', $this->pickupRequest->fresh()->status);

        // Status history recorded
        $this->assertDatabaseHas('donation_status_histories', [
            'pickup_request_id' => $this->pickupRequest->id,
            'status' => 'out_for_pickup',
        ]);
    }

    // =========================================================================
    // FEATURE 6: RECURRING DONATIONS SCHEDULING TESTS
    // =========================================================================

    public function test_restaurant_can_create_recurring_donation_schedule(): void
    {
        $response = $this->actingAs($this->restaurantUser)->post(route('restaurant.recurring.store'), [
            'schedule_name' => 'Midnight Dinner Buffet Rescue',
            'food_name' => 'Assorted Buffet Platters',
            'category' => 'Veg',
            'food_type' => 'Cooked Meals',
            'quantity' => 20,
            'unit' => 'servings',
            'description' => 'Trays from hotel banquet dinner buffet.',
            'pickup_address' => 'Connaught Place, Delhi',
            'frequency' => 'daily',
            'pickup_time_of_day' => '23:00',
            'start_date' => date('Y-m-d'),
        ]);

        $response->assertRedirect(route('restaurant.recurring.index'));
        $this->assertDatabaseHas('recurring_donations', [
            'restaurant_id' => $this->restaurant->id,
            'schedule_name' => 'Midnight Dinner Buffet Rescue',
            'is_active' => true,
        ]);
    }

    public function test_recurring_donation_service_generates_food_donations_without_duplicates(): void
    {
        $schedule = RecurringDonation::create([
            'restaurant_id' => $this->restaurant->id,
            'schedule_name' => 'Daily Bakery Leftover Surplus',
            'food_name' => 'Fresh Bread & Pastries',
            'category' => 'Bakery',
            'food_type' => 'Baked Goods',
            'quantity' => 15,
            'unit' => 'packets',
            'description' => 'Freshly baked today.',
            'pickup_address' => 'Connaught Place, Delhi',
            'frequency' => 'daily',
            'pickup_time_of_day' => '21:00',
            'start_date' => Carbon::today(),
            'is_active' => true,
        ]);

        $service = app(RecurringDonationService::class);

        // 1. First run generates donation
        $stats1 = $service->processDueSchedules();
        $this->assertEquals(1, $stats1['generated']);
        $this->assertDatabaseHas('food_donations', [
            'restaurant_id' => $this->restaurant->id,
            'food_name' => 'Fresh Bread & Pastries',
        ]);

        // 2. Immediate second run does NOT create duplicate
        $stats2 = $service->processDueSchedules();
        $this->assertEquals(0, $stats2['generated']);
        $this->assertEquals(1, $stats2['skipped']);
    }

    public function test_restaurant_can_toggle_recurring_schedule_status(): void
    {
        $schedule = RecurringDonation::create([
            'restaurant_id' => $this->restaurant->id,
            'schedule_name' => 'Sunday Brunch Pack',
            'food_name' => 'Brunch Items',
            'category' => 'Veg',
            'food_type' => 'Cooked Meals',
            'quantity' => 10,
            'unit' => 'boxes',
            'pickup_address' => 'Connaught Place',
            'frequency' => 'weekly',
            'pickup_time_of_day' => '15:00',
            'start_date' => Carbon::today(),
            'is_active' => true,
        ]);

        // Toggle to paused
        $response = $this->actingAs($this->restaurantUser)->post(route('restaurant.recurring.toggle', $schedule->id));
        $response->assertSessionHas('success');
        $this->assertFalse($schedule->fresh()->is_active);

        // Toggle to active
        $this->actingAs($this->restaurantUser)->post(route('restaurant.recurring.toggle', $schedule->id));
        $this->assertTrue($schedule->fresh()->is_active);
    }
}
