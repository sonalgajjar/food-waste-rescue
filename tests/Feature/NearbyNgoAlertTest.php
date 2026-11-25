<?php

namespace Tests\Feature;

use App\Events\FoodDonationCreated;
use App\Models\FoodDonation;
use App\Models\NGO;
use App\Models\Notification;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\FoodAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class NearbyNgoAlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_nearby_active_ngos_receive_alert_when_food_donation_is_posted(): void
    {
        // 1. Create Donor Restaurant in Connaught Place, Delhi (28.6315, 77.2167)
        $restaurantUser = User::factory()->create([
            'role' => 'restaurant',
            'status' => 'active',
            'latitude' => 28.6315,
            'longitude' => 77.2167,
        ]);

        $restaurant = Restaurant::create([
            'user_id' => $restaurantUser->id,
            'restaurant_name' => 'CP Gourmet Diner',
            'owner_name' => 'Chef Rahul',
            'license_number' => 'FSSAI-DEL-101',
            'phone' => '9876543210',
            'email' => 'cp@gourmet.com',
            'address' => 'Connaught Place',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'pincode' => '110001',
            'latitude' => 28.6315,
            'longitude' => 77.2167,
            'status' => 'active',
        ]);

        // 2. Create Nearby Active NGO at India Gate (~2.5 km away: 28.6129, 77.2295)
        $nearbyNgoUser = User::factory()->create([
            'role' => 'ngo',
            'status' => 'active',
            'latitude' => 28.6129,
            'longitude' => 77.2295,
        ]);

        $nearbyNgo = NGO::create([
            'user_id' => $nearbyNgoUser->id,
            'ngo_name' => 'Delhi Food Helpers',
            'registration_number' => 'NGO-DEL-001',
            'owner_name' => 'Amit Sharma',
            'phone' => '9811122233',
            'email' => 'helpers@delhingo.org',
            'address' => 'India Gate Circle',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'pincode' => '110001',
            'latitude' => 28.6129,
            'longitude' => 77.2295,
            'status' => 'active',
        ]);

        // 3. Create Distant Active NGO in Mumbai (~1150 km away: 19.0760, 72.8777)
        $distantNgoUser = User::factory()->create([
            'role' => 'ngo',
            'status' => 'active',
            'latitude' => 19.0760,
            'longitude' => 72.8777,
        ]);

        $distantNgo = NGO::create([
            'user_id' => $distantNgoUser->id,
            'ngo_name' => 'Mumbai Seva Trust',
            'registration_number' => 'NGO-MUM-999',
            'owner_name' => 'Pooja Patil',
            'phone' => '9822233344',
            'email' => 'mumbai@sevatrust.org',
            'address' => 'Bandra West',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'pincode' => '400050',
            'latitude' => 19.0760,
            'longitude' => 72.8777,
            'status' => 'active',
        ]);

        // 4. Create Inactive NGO in same neighborhood (pending approval)
        $inactiveNgoUser = User::factory()->create([
            'role' => 'ngo',
            'status' => 'pending',
            'latitude' => 28.6300,
            'longitude' => 77.2150,
        ]);

        $inactiveNgo = NGO::create([
            'user_id' => $inactiveNgoUser->id,
            'ngo_name' => 'Pending Delhi Relief',
            'registration_number' => 'NGO-DEL-PEND',
            'owner_name' => 'Pending User',
            'phone' => '9833344455',
            'email' => 'pending@relief.org',
            'address' => 'Connaught Place',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'pincode' => '110001',
            'latitude' => 28.6300,
            'longitude' => 77.2150,
            'status' => 'pending',
        ]);

        // 5. Post food donation as restaurant
        $response = $this->actingAs($restaurantUser)->post(route('restaurant.donations.store'), [
            'food_name' => 'Fresh Biryani & Curry',
            'category' => 'Veg',
            'food_type' => 'Cooked Meals',
            'quantity' => 25,
            'unit' => 'servings',
            'description' => 'Prepared this lunch, surplus untouched trays.',
            'cooked_time' => now()->subHour()->format('Y-m-d\TH:i'),
            'expiry_time' => now()->addHours(4)->format('Y-m-d\TH:i'),
            'pickup_address' => 'Block B, Connaught Place, New Delhi',
            'latitude' => 28.6315,
            'longitude' => 77.2167,
            'food_safety_declaration' => 1,
        ]);

        $response->assertRedirect(route('restaurant.donations.index'));
        $response->assertSessionHas('success');

        // Verify donation was saved
        $donation = FoodDonation::where('food_name', 'Fresh Biryani & Curry')->first();
        $this->assertNotNull($donation);

        // 6. Assert Nearby NGO received in-app Notification
        $this->assertDatabaseHas('notifications', [
            'user_id' => $nearbyNgoUser->id,
            'type' => 'food_alert',
            'food_donation_id' => $donation->id,
        ]);

        $notification = Notification::where('user_id', $nearbyNgoUser->id)
            ->where('food_donation_id', $donation->id)
            ->first();

        $this->assertStringContainsString('CP Gourmet Diner', $notification->message);
        $this->assertStringContainsString('Fresh Biryani & Curry', $notification->message);
        $this->assertEquals(route('ngo.donations.show', $donation->id), $notification->link);

        // 7. Assert Distant NGO did NOT receive alert
        $this->assertDatabaseMissing('notifications', [
            'user_id' => $distantNgoUser->id,
            'food_donation_id' => $donation->id,
        ]);

        // 8. Assert Inactive NGO did NOT receive alert
        $this->assertDatabaseMissing('notifications', [
            'user_id' => $inactiveNgoUser->id,
            'food_donation_id' => $donation->id,
        ]);
    }

    public function test_fallback_to_city_match_when_coordinates_are_missing(): void
    {
        $restaurantUser = User::factory()->create([
            'role' => 'restaurant',
            'status' => 'active',
        ]);

        $restaurant = Restaurant::create([
            'user_id' => $restaurantUser->id,
            'restaurant_name' => 'Ahmedabad Kitchen',
            'owner_name' => 'Owner',
            'phone' => '9998887776',
            'email' => 'ahmedabad@kitchen.com',
            'address' => 'Navrangpura',
            'city' => 'Ahmedabad',
            'state' => 'Gujarat',
            'pincode' => '380009',
            'latitude' => null,
            'longitude' => null,
            'status' => 'active',
        ]);

        $ahmedabadNgoUser = User::factory()->create(['role' => 'ngo', 'status' => 'active']);
        $ahmedabadNgo = NGO::create([
            'user_id' => $ahmedabadNgoUser->id,
            'ngo_name' => 'Ahmedabad Care',
            'registration_number' => 'NGO-AHM-01',
            'owner_name' => 'Care Leader',
            'phone' => '9887776665',
            'email' => 'care@ahmedabad.org',
            'address' => 'Ashram Road',
            'city' => 'Ahmedabad',
            'state' => 'Gujarat',
            'pincode' => '380009',
            'latitude' => null,
            'longitude' => null,
            'status' => 'active',
        ]);

        $suratNgoUser = User::factory()->create(['role' => 'ngo', 'status' => 'active']);
        $suratNgo = NGO::create([
            'user_id' => $suratNgoUser->id,
            'ngo_name' => 'Surat Seva',
            'registration_number' => 'NGO-SRT-01',
            'owner_name' => 'Surat Leader',
            'phone' => '9776665554',
            'email' => 'seva@surat.org',
            'address' => 'Ring Road',
            'city' => 'Surat',
            'state' => 'Gujarat',
            'pincode' => '395002',
            'latitude' => null,
            'longitude' => null,
            'status' => 'active',
        ]);

        $donation = FoodDonation::create([
            'restaurant_id' => $restaurant->id,
            'food_name' => 'Khichdi & Kadhi',
            'category' => 'Veg',
            'food_type' => 'Cooked Meals',
            'quantity' => 15,
            'unit' => 'servings',
            'cooked_time' => now()->subHour(),
            'expiry_time' => now()->addHours(3),
            'pickup_address' => 'Navrangpura, Ahmedabad',
            'latitude' => null,
            'longitude' => null,
            'status' => 'available',
        ]);

        $service = app(FoodAlertService::class);
        $alerted = $service->notifyNearbyNgos($donation);

        $this->assertEquals(1, $alerted);

        // Ahmedabad NGO alerted
        $this->assertDatabaseHas('notifications', [
            'user_id' => $ahmedabadNgoUser->id,
            'food_donation_id' => $donation->id,
            'type' => 'food_alert',
        ]);

        // Surat NGO not alerted
        $this->assertDatabaseMissing('notifications', [
            'user_id' => $suratNgoUser->id,
            'food_donation_id' => $donation->id,
        ]);
    }

    public function test_food_donation_created_event_is_dispatched(): void
    {
        Event::fake([FoodDonationCreated::class]);

        $restaurantUser = User::factory()->create(['role' => 'restaurant', 'status' => 'active']);
        $restaurant = Restaurant::create([
            'user_id' => $restaurantUser->id,
            'restaurant_name' => 'Event Cafe',
            'owner_name' => 'Host',
            'phone' => '9112223334',
            'email' => 'event@cafe.com',
            'address' => 'Connaught Place',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'pincode' => '110001',
            'status' => 'active',
        ]);

        $this->actingAs($restaurantUser)->post(route('restaurant.donations.store'), [
            'food_name' => 'Party Leftovers',
            'category' => 'Veg',
            'food_type' => 'Cooked Meals',
            'quantity' => 10,
            'unit' => 'kg',
            'cooked_time' => now()->subHour()->format('Y-m-d\TH:i'),
            'expiry_time' => now()->addHours(3)->format('Y-m-d\TH:i'),
            'pickup_address' => 'Connaught Place',
            'latitude' => 28.6315,
            'longitude' => 77.2167,
            'food_safety_declaration' => 1,
        ]);

        Event::assertDispatched(FoodDonationCreated::class, function ($event) {
            return $event->donation->food_name === 'Party Leftovers';
        });
    }

    public function test_ngo_dashboard_displays_recent_nearby_food_alerts(): void
    {
        $ngoUser = User::factory()->create(['role' => 'ngo', 'status' => 'active']);
        $ngo = NGO::create([
            'user_id' => $ngoUser->id,
            'ngo_name' => 'Green Earth Care',
            'registration_number' => 'NGO-GEC-01',
            'owner_name' => 'Priya',
            'phone' => '9123456780',
            'email' => 'priya@gec.org',
            'address' => 'Central Delhi',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'pincode' => '110001',
            'status' => 'active',
        ]);

        $restaurantUser = User::factory()->create(['role' => 'restaurant', 'status' => 'active']);
        $restaurant = Restaurant::create([
            'user_id' => $restaurantUser->id,
            'restaurant_name' => 'Delhi Diner',
            'owner_name' => 'Diner Owner',
            'phone' => '9123456781',
            'email' => 'diner@delhi.com',
            'address' => 'Central Delhi',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'pincode' => '110001',
            'status' => 'active',
        ]);

        $donation = FoodDonation::create([
            'restaurant_id' => $restaurant->id,
            'food_name' => 'Paneer Tikka Trays',
            'category' => 'Veg',
            'food_type' => 'Cooked Meals',
            'quantity' => 8,
            'unit' => 'kg',
            'cooked_time' => now()->subHour(),
            'expiry_time' => now()->addHours(4),
            'pickup_address' => 'Central Delhi',
            'status' => 'available',
        ]);

        Notification::create([
            'user_id' => $ngoUser->id,
            'type' => 'food_alert',
            'title' => 'Nearby Surplus Food Alert',
            'message' => 'Fresh surplus food posted: "Paneer Tikka Trays" from Delhi Diner.',
            'link' => route('ngo.donations.show', $donation->id),
            'food_donation_id' => $donation->id,
            'is_read' => false,
        ]);

        $response = $this->actingAs($ngoUser)->get(route('ngo.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Nearby Alert');
        $response->assertSee('Paneer Tikka Trays');
        $response->assertSee(route('ngo.donations.show', $donation->id));
    }
}
