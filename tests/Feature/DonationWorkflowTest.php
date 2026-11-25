<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Restaurant;
use App\Models\NGO;
use App\Models\FoodDonation;

class DonationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_restaurant_can_create_donation()
    {
        $user = User::factory()->create(['role' => 'restaurant', 'status' => 'active']);
        $restaurant = Restaurant::create([
            'user_id' => $user->id,
            'restaurant_name' => 'Test Rest',
            'owner_name' => 'Owner',
            'phone' => '1234567890',
            'email' => 'test@rest.com',
            'address' => 'Test Address',
            'city' => 'City',
            'state' => 'State',
            'pincode' => '123456',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->post('/restaurant/donations', [
            'food_name' => 'Test Food',
            'category' => 'Veg',
            'food_type' => 'Cooked Meals',
            'quantity' => 10,
            'unit' => 'servings',
            'cooked_time' => now()->format('Y-m-d\TH:i'),
            'expiry_time' => now()->addHours(5)->format('Y-m-d\TH:i'),
            'pickup_address' => 'Test Address',
            'latitude' => 28.0,
            'longitude' => 77.0,
            'food_safety_declaration' => 1,
        ]);

        $response->assertRedirect('/restaurant/donations');
        $this->assertDatabaseHas('food_donations', [
            'food_name' => 'Test Food',
            'status' => 'available',
        ]);
    }
}
