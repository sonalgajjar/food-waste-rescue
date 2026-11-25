<?php

namespace Tests\Feature;

use App\Models\FoodDonation;
use App\Models\NGO;
use App\Models\PickupRequest;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PickupWorkflowComprehensiveTest extends TestCase
{
    use RefreshDatabase;

    protected User $restaurantUser;
    protected Restaurant $restaurant;
    protected User $ngoUser;
    protected NGO $ngo;
    protected FoodDonation $donation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->restaurantUser = User::create([
            'name' => 'Chef Mario',
            'email' => 'mario@restaurant.com',
            'password' => bcrypt('password'),
            'role' => 'restaurant',
            'status' => 'active',
            'phone' => '9876543210',
            'address' => '100 Food St',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'pincode' => '110001',
            'latitude' => 28.6139,
            'longitude' => 77.2090,
        ]);

        $this->restaurant = Restaurant::create([
            'user_id' => $this->restaurantUser->id,
            'restaurant_name' => 'Mario Bistro',
            'owner_name' => 'Mario',
            'license_number' => 'FSSAI-TEST-99',
            'phone' => '9876543210',
            'email' => 'mario@restaurant.com',
            'address' => '100 Food St',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'pincode' => '110001',
            'latitude' => 28.6139,
            'longitude' => 77.2090,
            'status' => 'active',
        ]);

        $this->ngoUser = User::create([
            'name' => 'Care Foundation User',
            'email' => 'contact@carengo.org',
            'password' => bcrypt('password'),
            'role' => 'ngo',
            'status' => 'active',
            'phone' => '9876543222',
            'address' => '200 Hope St',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'pincode' => '110002',
            'latitude' => 28.6250,
            'longitude' => 77.2150,
        ]);

        $this->ngo = NGO::create([
            'user_id' => $this->ngoUser->id,
            'ngo_name' => 'Care Food Relief',
            'registration_number' => 'NGO-REG-999',
            'owner_name' => 'Care Director',
            'phone' => '9876543222',
            'email' => 'contact@carengo.org',
            'address' => '200 Hope St',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'pincode' => '110002',
            'latitude' => 28.6250,
            'longitude' => 77.2150,
            'status' => 'active',
        ]);

        $this->donation = FoodDonation::create([
            'restaurant_id' => $this->restaurant->id,
            'food_name' => 'Fresh Rice and Curry',
            'category' => 'Veg',
            'food_type' => 'Cooked Meals',
            'quantity' => 25,
            'unit' => 'servings',
            'cooked_time' => now()->subHour(),
            'expiry_time' => now()->addHours(6),
            'pickup_address' => '100 Food St',
            'latitude' => 28.6139,
            'longitude' => 77.2090,
            'status' => 'available',
        ]);
    }

    public function test_ngo_can_request_available_food(): void
    {
        $response = $this->actingAs($this->ngoUser)->post("/ngo/donations/{$this->donation->id}/request", [
            'pickup_time' => now()->addHours(2)->format('Y-m-d\TH:i'),
            'remarks' => 'Volunteer pickup with van',
        ]);

        $response->assertRedirect('/ngo/history');
        $this->assertDatabaseHas('pickup_requests', [
            'food_donation_id' => $this->donation->id,
            'ngo_id' => $this->ngo->id,
            'status' => 'pending',
        ]);

        $this->assertEquals('requested', $this->donation->fresh()->status);
    }

    public function test_duplicate_pickup_request_is_prevented(): void
    {
        // First request
        PickupRequest::create([
            'food_donation_id' => $this->donation->id,
            'ngo_id' => $this->ngo->id,
            'pickup_time' => now()->addHours(2),
            'status' => 'pending',
        ]);
        $this->donation->update(['status' => 'requested']);

        // Second request attempted
        $response = $this->actingAs($this->ngoUser)->post("/ngo/donations/{$this->donation->id}/request", [
            'pickup_time' => now()->addHours(3)->format('Y-m-d\TH:i'),
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals(1, PickupRequest::where('food_donation_id', $this->donation->id)->count());
    }

    public function test_claiming_expired_food_is_blocked(): void
    {
        $expiredDonation = FoodDonation::create([
            'restaurant_id' => $this->restaurant->id,
            'food_name' => 'Old Breads',
            'category' => 'Bakery',
            'food_type' => 'Baked Goods',
            'quantity' => 10,
            'unit' => 'packets',
            'cooked_time' => now()->subDays(2),
            'expiry_time' => now()->subHour(), // Expired
            'pickup_address' => '100 Food St',
            'latitude' => 28.6139,
            'longitude' => 77.2090,
            'status' => 'available',
        ]);

        $response = $this->actingAs($this->ngoUser)->post("/ngo/donations/{$expiredDonation->id}/request", [
            'pickup_time' => now()->addHour()->format('Y-m-d\TH:i'),
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('pickup_requests', [
            'food_donation_id' => $expiredDonation->id,
        ]);
    }

    public function test_complete_pickup_lifecycle_pending_to_completed(): void
    {
        // 1. Pending Request
        $request = PickupRequest::create([
            'food_donation_id' => $this->donation->id,
            'ngo_id' => $this->ngo->id,
            'pickup_time' => now()->addHours(2),
            'status' => 'pending',
        ]);
        $this->donation->update(['status' => 'requested']);

        // 2. Restaurant Accepts
        $response = $this->actingAs($this->restaurantUser)->post("/restaurant/requests/{$request->id}/status", [
            'status' => 'accepted',
        ]);
        $response->assertSessionHas('success');
        $this->assertEquals('accepted', $request->fresh()->status);
        $this->assertEquals('approved', $this->donation->fresh()->status);

        // 3. Direct status change to 'picked' is strictly blocked
        $blocked = $this->actingAs($this->restaurantUser)->post("/restaurant/requests/{$request->id}/status", [
            'status' => 'picked',
        ]);
        $blocked->assertSessionHas('error');

        // 4. NGO Starts Pickup Journey
        $this->actingAs($this->ngoUser)->post(route('handover.start', $request->id));
        $this->assertEquals('out_for_pickup', $request->fresh()->status);

        // 5. Handover Verified via Secure OTP
        $verification = $request->fresh()->verification;
        $otp = $verification->getDecryptedOtp();
        $this->actingAs($this->ngoUser)->post(route('handover.verify.otp', $request->id), [
            'otp' => $otp,
        ]);
        $this->assertEquals('handover_confirmed', $request->fresh()->status);

        // 6. Complete Handover
        $response = $this->actingAs($this->restaurantUser)->post(route('handover.complete', $request->id));
        $response->assertSessionHas('success');
        $this->assertEquals('completed', $request->fresh()->status);
        $this->assertEquals('completed', $this->donation->fresh()->status);
    }

    public function test_restaurant_can_reject_pending_request_and_food_becomes_available(): void
    {
        $request = PickupRequest::create([
            'food_donation_id' => $this->donation->id,
            'ngo_id' => $this->ngo->id,
            'pickup_time' => now()->addHours(2),
            'status' => 'pending',
        ]);
        $this->donation->update(['status' => 'requested']);

        $response = $this->actingAs($this->restaurantUser)->post("/restaurant/requests/{$request->id}/status", [
            'status' => 'rejected',
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals('rejected', $request->fresh()->status);
        $this->assertEquals('available', $this->donation->fresh()->status);
    }

    public function test_invalid_status_transition_is_blocked(): void
    {
        $request = PickupRequest::create([
            'food_donation_id' => $this->donation->id,
            'ngo_id' => $this->ngo->id,
            'pickup_time' => now()->addHours(2),
            'status' => 'pending',
        ]);

        // Attempting to jump directly from pending to completed should fail
        $response = $this->actingAs($this->restaurantUser)->post("/restaurant/requests/{$request->id}/status", [
            'status' => 'completed',
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals('pending', $request->fresh()->status);
    }

    public function test_ngo_can_cancel_pending_request(): void
    {
        $request = PickupRequest::create([
            'food_donation_id' => $this->donation->id,
            'ngo_id' => $this->ngo->id,
            'pickup_time' => now()->addHours(2),
            'status' => 'pending',
        ]);
        $this->donation->update(['status' => 'requested']);

        $response = $this->actingAs($this->ngoUser)->post("/ngo/requests/{$request->id}/cancel");
        $response->assertSessionHas('success');

        $this->assertEquals('rejected', $request->fresh()->status);
        $this->assertEquals('available', $this->donation->fresh()->status);
    }
}
