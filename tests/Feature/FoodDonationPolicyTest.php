<?php

namespace Tests\Feature;

use App\Models\FoodDonation;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FoodDonationPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $restaurantUser1;
    protected Restaurant $restaurant1;
    protected User $restaurantUser2;
    protected Restaurant $restaurant2;
    protected FoodDonation $donation1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'status' => 'active',
            'phone' => '1234567890',
            'address' => 'HQ',
            'city' => 'City',
            'state' => 'State',
            'pincode' => '123456',
        ]);

        $this->restaurantUser1 = User::create([
            'name' => 'Rest Owner 1',
            'email' => 'rest1@test.com',
            'password' => bcrypt('password'),
            'role' => 'restaurant',
            'status' => 'active',
            'phone' => '1111111111',
            'address' => 'Address 1',
            'city' => 'City',
            'state' => 'State',
            'pincode' => '123456',
        ]);

        $this->restaurant1 = Restaurant::create([
            'user_id' => $this->restaurantUser1->id,
            'restaurant_name' => 'Restaurant One',
            'owner_name' => 'Owner 1',
            'license_number' => 'FSSAI-001',
            'phone' => '1111111111',
            'email' => 'rest1@test.com',
            'address' => 'Address 1',
            'city' => 'City',
            'state' => 'State',
            'pincode' => '123456',
            'status' => 'active',
        ]);

        $this->restaurantUser2 = User::create([
            'name' => 'Rest Owner 2',
            'email' => 'rest2@test.com',
            'password' => bcrypt('password'),
            'role' => 'restaurant',
            'status' => 'active',
            'phone' => '2222222222',
            'address' => 'Address 2',
            'city' => 'City',
            'state' => 'State',
            'pincode' => '123456',
        ]);

        $this->restaurant2 = Restaurant::create([
            'user_id' => $this->restaurantUser2->id,
            'restaurant_name' => 'Restaurant Two',
            'owner_name' => 'Owner 2',
            'license_number' => 'FSSAI-002',
            'phone' => '2222222222',
            'email' => 'rest2@test.com',
            'address' => 'Address 2',
            'city' => 'City',
            'state' => 'State',
            'pincode' => '123456',
            'status' => 'active',
        ]);

        $this->donation1 = FoodDonation::create([
            'restaurant_id' => $this->restaurant1->id,
            'food_name' => 'Delicious Meals',
            'category' => 'Veg',
            'food_type' => 'Cooked Meals',
            'quantity' => 20,
            'unit' => 'servings',
            'cooked_time' => now()->subHour(),
            'expiry_time' => now()->addHours(6),
            'pickup_address' => 'Address 1',
            'latitude' => 28.6139,
            'longitude' => 77.2090,
            'status' => 'available',
        ]);
    }

    public function test_owner_restaurant_can_delete_own_donation(): void
    {
        $response = $this->actingAs($this->restaurantUser1)
            ->delete("/restaurant/donations/{$this->donation1->id}");

        $response->assertRedirect('/restaurant/donations');
        $this->assertDatabaseMissing('food_donations', ['id' => $this->donation1->id]);
    }

    public function test_unauthorized_restaurant_cannot_delete_other_restaurant_donation(): void
    {
        $response = $this->actingAs($this->restaurantUser2)
            ->delete("/restaurant/donations/{$this->donation1->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('food_donations', ['id' => $this->donation1->id]);
    }

    public function test_unauthorized_restaurant_cannot_edit_other_restaurant_donation(): void
    {
        $response = $this->actingAs($this->restaurantUser2)
            ->get("/restaurant/donations/{$this->donation1->id}/edit");

        $response->assertStatus(403);
    }

    public function test_admin_can_delete_donation(): void
    {
        $this->assertTrue($this->admin->can('delete', $this->donation1));
    }
}
