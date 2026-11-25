<?php

namespace Tests\Feature;

use App\Models\FoodDonation;
use App\Models\NGO;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationAndDistanceSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_food_donation_calculates_distance_accurately(): void
    {
        $user = User::create([
            'name' => 'Rest Donor',
            'email' => 'donor@test.com',
            'password' => bcrypt('password'),
            'role' => 'restaurant',
            'status' => 'active',
            'phone' => '1234567890',
            'address' => 'Connaught Place',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'pincode' => '110001',
            'latitude' => 28.6315,
            'longitude' => 77.2167,
        ]);

        $restaurant = Restaurant::create([
            'user_id' => $user->id,
            'restaurant_name' => 'CP Diner',
            'owner_name' => 'Owner',
            'license_number' => 'FSSAI-CP-1',
            'phone' => '1234567890',
            'email' => 'donor@test.com',
            'address' => 'Connaught Place',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'pincode' => '110001',
            'latitude' => 28.6315,
            'longitude' => 77.2167,
            'status' => 'active',
        ]);

        $donation = FoodDonation::create([
            'restaurant_id' => $restaurant->id,
            'food_name' => 'Hot Buffet',
            'category' => 'Veg',
            'food_type' => 'Cooked Meals',
            'quantity' => 20,
            'unit' => 'servings',
            'cooked_time' => now()->subHour(),
            'expiry_time' => now()->addHours(4),
            'pickup_address' => 'Connaught Place',
            'latitude' => 28.6315,
            'longitude' => 77.2167,
            'status' => 'available',
        ]);

        // Coordinates approx 2.5 km away (India Gate: 28.6129, 77.2295)
        $distance = $donation->calculateDistanceTo(28.6129, 77.2295);

        $this->assertNotNull($distance);
        $this->assertGreaterThan(1.5, $distance);
        $this->assertLessThan(3.5, $distance);
    }

    public function test_registration_does_not_silently_assign_ahmedabad_coordinates(): void
    {
        $response = $this->post('/register', [
            'role' => 'restaurant',
            'name' => 'Mumbai Cafe Owner',
            'email' => 'mumbai@cafe.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '9999988888',
            'address' => 'Marine Drive',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'pincode' => '400020',
            'restaurant_name' => 'Marine Cafe',
            'owner_name' => 'Mumbai Owner',
            'license_number' => 'FSSAI-MUM-100',
            'opening_time' => '08:00',
            'closing_time' => '23:00',
        ]);

        $response->assertRedirect('/login');

        $user = User::where('email', 'mumbai@cafe.com')->first();
        $this->assertNotNull($user);

        // Crucial requirement: NEVER silently assign Ahmedabad coordinates (23.0225, 72.5714) to users from other locations
        if ($user->latitude !== null) {
            $this->assertNotEquals(23.0225000, $user->latitude);
        }
    }
}
