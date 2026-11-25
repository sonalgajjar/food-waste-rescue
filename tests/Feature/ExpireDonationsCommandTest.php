<?php

namespace Tests\Feature;

use App\Models\FoodDonation;
use App\Models\NGO;
use App\Models\PickupRequest;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpireDonationsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_donations_expire_command_marks_expired_donations_and_updates_requests(): void
    {
        $donor = User::create([
            'name' => 'Donor Rest',
            'email' => 'donor@test.com',
            'password' => bcrypt('password'),
            'role' => 'restaurant',
            'status' => 'active',
            'phone' => '1234567890',
            'address' => 'Addr',
            'city' => 'City',
            'state' => 'State',
            'pincode' => '123456',
        ]);

        $restaurant = Restaurant::create([
            'user_id' => $donor->id,
            'restaurant_name' => 'Donor Restaurant',
            'owner_name' => 'Owner',
            'license_number' => 'FSSAI-099',
            'phone' => '1234567890',
            'email' => 'donor@test.com',
            'address' => 'Addr',
            'city' => 'City',
            'state' => 'State',
            'pincode' => '123456',
            'status' => 'active',
        ]);

        $ngoUser = User::create([
            'name' => 'NGO User',
            'email' => 'ngo@test.com',
            'password' => bcrypt('password'),
            'role' => 'ngo',
            'status' => 'active',
            'phone' => '9876543210',
            'address' => 'NGO Addr',
            'city' => 'City',
            'state' => 'State',
            'pincode' => '123456',
        ]);

        $ngo = NGO::create([
            'user_id' => $ngoUser->id,
            'ngo_name' => 'Relief NGO',
            'registration_number' => 'REG-001',
            'owner_name' => 'Director',
            'phone' => '9876543210',
            'email' => 'ngo@test.com',
            'address' => 'NGO Addr',
            'city' => 'City',
            'state' => 'State',
            'pincode' => '123456',
            'status' => 'active',
        ]);

        // 1. Available but expired
        $expiredAvailable = FoodDonation::create([
            'restaurant_id' => $restaurant->id,
            'food_name' => 'Available Expired Rice',
            'category' => 'Veg',
            'food_type' => 'Cooked Meals',
            'quantity' => 10,
            'unit' => 'servings',
            'cooked_time' => now()->subDay(),
            'expiry_time' => now()->subHour(),
            'pickup_address' => 'Addr',
            'status' => 'available',
        ]);

        // 2. Approved (Accepted request) but expired
        $expiredApproved = FoodDonation::create([
            'restaurant_id' => $restaurant->id,
            'food_name' => 'Approved Expired Bread',
            'category' => 'Bakery',
            'food_type' => 'Baked Goods',
            'quantity' => 15,
            'unit' => 'packets',
            'cooked_time' => now()->subDay(),
            'expiry_time' => now()->subMinutes(30),
            'pickup_address' => 'Addr',
            'status' => 'approved',
        ]);

        $activeRequest = PickupRequest::create([
            'food_donation_id' => $expiredApproved->id,
            'ngo_id' => $ngo->id,
            'pickup_time' => now()->subMinutes(15),
            'status' => 'accepted',
        ]);

        // 3. Valid future donation (should NOT expire)
        $futureDonation = FoodDonation::create([
            'restaurant_id' => $restaurant->id,
            'food_name' => 'Fresh Dal Makhani',
            'category' => 'Veg',
            'food_type' => 'Cooked Meals',
            'quantity' => 20,
            'unit' => 'servings',
            'cooked_time' => now()->subHour(),
            'expiry_time' => now()->addHours(5),
            'pickup_address' => 'Addr',
            'status' => 'available',
        ]);

        $this->artisan('donations:expire')->assertSuccessful();

        $this->assertEquals('expired', $expiredAvailable->fresh()->status);
        $this->assertEquals('expired', $expiredApproved->fresh()->status);
        $this->assertEquals('rejected', $activeRequest->fresh()->status);
        $this->assertEquals('available', $futureDonation->fresh()->status);
    }
}
