<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                [
                    'lat' => '28.6139',
                    'lon' => '77.2090',
                ]
            ], 200),
        ]);

        $response = $this->post('/register', [
            'role' => 'restaurant',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'phone' => '1234567890',
            'address' => '123 Connaught Place',
            'city' => 'New Delhi',
            'state' => 'Delhi',
            'pincode' => '110001',
            'restaurant_name' => 'Test Bistro',
            'owner_name' => 'Test Owner',
            'license_number' => 'FSSAI-12345678',
            'opening_time' => '09:00',
            'closing_time' => '22:00',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'status' => 'pending',
            'role' => 'restaurant',
        ]);

        $this->assertDatabaseHas('restaurants', [
            'restaurant_name' => 'Test Bistro',
            'status' => 'pending',
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_duplicate_license_number_fails_validation(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                [
                    'lat' => '28.6139',
                    'lon' => '77.2090',
                ]
            ], 200),
        ]);

        // First registration
        $this->post('/register', [
            'role' => 'restaurant',
            'name' => 'First User',
            'email' => 'first@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'phone' => '1234567890',
            'address' => '123 Connaught Place',
            'city' => 'New Delhi',
            'state' => 'Delhi',
            'pincode' => '110001',
            'restaurant_name' => 'First Bistro',
            'owner_name' => 'First Owner',
            'license_number' => 'UNIQUE-12345',
            'opening_time' => '09:00',
            'closing_time' => '22:00',
        ]);

        // Second registration with the same license number
        $response = $this->post('/register', [
            'role' => 'restaurant',
            'name' => 'Second User',
            'email' => 'second@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'phone' => '9876543210',
            'address' => '456 Connaught Place',
            'city' => 'New Delhi',
            'state' => 'Delhi',
            'pincode' => '110001',
            'restaurant_name' => 'Second Bistro',
            'owner_name' => 'Second Owner',
            'license_number' => 'UNIQUE-12345',
            'opening_time' => '09:00',
            'closing_time' => '22:00',
        ]);

        $response->assertSessionHasErrors(['license_number']);
        $this->assertDatabaseMissing('users', ['email' => 'second@example.com']);
    }
}

