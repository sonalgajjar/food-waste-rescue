<?php

namespace Tests\Feature;

use App\Models\NGO;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminApprovalAndStatusTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

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
            'address' => 'Admin St',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'pincode' => '110001',
        ]);
    }

    public function test_pending_user_cannot_login_and_receives_pending_message(): void
    {
        $pendingUser = User::create([
            'name' => 'Pending Rest User',
            'email' => 'pending@test.com',
            'password' => bcrypt('password'),
            'role' => 'restaurant',
            'status' => 'pending',
            'phone' => '1234567890',
            'address' => 'Address',
            'city' => 'City',
            'state' => 'State',
            'pincode' => '123456',
        ]);

        $response = $this->post('/login', [
            'email' => 'pending@test.com',
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors(['email']);
    }

    public function test_rejected_user_cannot_login_and_receives_rejected_message(): void
    {
        $rejectedUser = User::create([
            'name' => 'Rejected Rest User',
            'email' => 'rejected@test.com',
            'password' => bcrypt('password'),
            'role' => 'restaurant',
            'status' => 'rejected',
            'phone' => '1234567890',
            'address' => 'Address',
            'city' => 'City',
            'state' => 'State',
            'pincode' => '123456',
        ]);

        $response = $this->post('/login', [
            'email' => 'rejected@test.com',
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors(['email']);
    }

    public function test_admin_approving_restaurant_activates_both_records(): void
    {
        $user = User::create([
            'name' => 'Pending Owner',
            'email' => 'owner@test.com',
            'password' => bcrypt('password'),
            'role' => 'restaurant',
            'status' => 'pending',
            'phone' => '9876543210',
            'address' => 'Street 1',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'pincode' => '110001',
        ]);

        $restaurant = Restaurant::create([
            'user_id' => $user->id,
            'restaurant_name' => 'Tasty Treats',
            'owner_name' => 'Owner',
            'license_number' => 'FSSAI-888',
            'phone' => '9876543210',
            'email' => 'owner@test.com',
            'address' => 'Street 1',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'pincode' => '110001',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->post("/admin/restaurants/{$restaurant->id}/approve", [
            'status' => 'active',
        ]);

        $response->assertRedirect();
        $this->assertEquals('active', $restaurant->fresh()->status);
        $this->assertEquals('active', $user->fresh()->status);
    }

    public function test_admin_rejecting_restaurant_sets_both_records_to_rejected(): void
    {
        $user = User::create([
            'name' => 'Suspicious Owner',
            'email' => 'suspicious@test.com',
            'password' => bcrypt('password'),
            'role' => 'restaurant',
            'status' => 'pending',
            'phone' => '9876543211',
            'address' => 'Street 2',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'pincode' => '110001',
        ]);

        $restaurant = Restaurant::create([
            'user_id' => $user->id,
            'restaurant_name' => 'Suspicious Diner',
            'owner_name' => 'Owner',
            'license_number' => 'FSSAI-999',
            'phone' => '9876543211',
            'email' => 'suspicious@test.com',
            'address' => 'Street 2',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'pincode' => '110001',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->post("/admin/restaurants/{$restaurant->id}/approve", [
            'status' => 'rejected',
        ]);

        $response->assertRedirect();
        $this->assertEquals('rejected', $restaurant->fresh()->status);
        $this->assertEquals('rejected', $user->fresh()->status);
    }

    public function test_admin_can_export_donations_excel_report(): void
    {
        $export = new \App\Exports\DonationsExport();
        $this->assertInstanceOf(\Illuminate\Support\Enumerable::class, $export->collection());
        $this->assertIsArray($export->headings());
        $this->assertNotEmpty($export->headings());

        $firstRow = $export->collection()->first();
        if ($firstRow) {
            $mapped = $export->map($firstRow);
            $this->assertIsArray($mapped);
            $this->assertCount(count($export->headings()), $mapped);
        }
    }
}
