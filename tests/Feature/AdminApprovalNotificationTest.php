<?php

namespace Tests\Feature;

use App\Mail\AccountApprovedMail;
use App\Models\NGO;
use App\Models\Notification;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminApprovalNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@foodrescue.test',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'status' => 'active',
            'phone' => '1234567890',
            'address' => 'Admin HQ',
            'city' => 'Metro',
            'state' => 'State',
            'pincode' => '100001',
        ]);
    }

    public function test_restaurant_approval_sends_email_notification(): void
    {
        Mail::fake();

        $user = User::create([
            'name' => 'Green Diner',
            'email' => 'greendiner@example.com',
            'password' => bcrypt('password'),
            'role' => 'restaurant',
            'status' => 'pending',
            'phone' => '9876543210',
            'address' => '123 Green Way',
            'city' => 'Metro',
            'state' => 'State',
            'pincode' => '100002',
        ]);

        $restaurant = Restaurant::create([
            'user_id' => $user->id,
            'restaurant_name' => 'Green Diner',
            'owner_name' => 'Alice Chef',
            'phone' => '9876543210',
            'email' => 'greendiner@example.com',
            'address' => '123 Green Way',
            'city' => 'Metro',
            'state' => 'State',
            'pincode' => '100002',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->post("/admin/restaurants/{$restaurant->id}/approve", [
            'status' => 'active',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertEquals('active', $restaurant->fresh()->status);
        $this->assertEquals('active', $user->fresh()->status);

        // Verify approval email was sent to the user's registered email
        Mail::assertSent(AccountApprovedMail::class, function ($mail) use ($user) {
            return $mail->hasTo('greendiner@example.com') &&
                   $mail->userName === 'Green Diner' &&
                   $mail->accountType === 'Restaurant' &&
                   $mail->loginUrl === route('login');
        });

        // Verify in-app database notification was created
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'title' => 'Registration Approved',
        ]);
    }

    public function test_ngo_approval_sends_email_notification(): void
    {
        Mail::fake();

        $user = User::create([
            'name' => 'Helping Hands NGO',
            'email' => 'helpinghands@example.com',
            'password' => bcrypt('password'),
            'role' => 'ngo',
            'status' => 'pending',
            'phone' => '9123456780',
            'address' => '456 Charity Lane',
            'city' => 'Metro',
            'state' => 'State',
            'pincode' => '100003',
        ]);

        $ngo = NGO::create([
            'user_id' => $user->id,
            'ngo_name' => 'Helping Hands NGO',
            'registration_number' => 'REG-12345',
            'owner_name' => 'Bob Leader',
            'phone' => '9123456780',
            'email' => 'helpinghands@example.com',
            'address' => '456 Charity Lane',
            'city' => 'Metro',
            'state' => 'State',
            'pincode' => '100003',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->post("/admin/ngos/{$ngo->id}/approve", [
            'status' => 'active',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertEquals('active', $ngo->fresh()->status);
        $this->assertEquals('active', $user->fresh()->status);

        Mail::assertSent(AccountApprovedMail::class, function ($mail) {
            return $mail->hasTo('helpinghands@example.com') &&
                   $mail->userName === 'Helping Hands NGO' &&
                   $mail->accountType === 'NGO';
        });

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'title' => 'Registration Approved',
        ]);
    }

    public function test_user_toggle_activation_sends_email_notification(): void
    {
        Mail::fake();

        $user = User::create([
            'name' => 'Toggle Kitchen User',
            'email' => 'toggle@example.com',
            'password' => bcrypt('password'),
            'role' => 'restaurant',
            'status' => 'inactive',
            'phone' => '9876543219',
            'address' => '808 Toggle St',
            'city' => 'Metro',
            'state' => 'State',
            'pincode' => '100008',
        ]);

        Restaurant::create([
            'user_id' => $user->id,
            'restaurant_name' => 'Toggle Kitchen',
            'owner_name' => 'Toggle Owner',
            'phone' => '9876543219',
            'email' => 'toggle@example.com',
            'address' => '808 Toggle St',
            'city' => 'Metro',
            'state' => 'State',
            'pincode' => '100008',
            'status' => 'inactive',
        ]);

        $response = $this->actingAs($this->admin)->post("/admin/users/{$user->id}/toggle");

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertEquals('active', $user->fresh()->status);

        Mail::assertSent(AccountApprovedMail::class, 1);
        Mail::assertSent(AccountApprovedMail::class, function ($mail) {
            return $mail->hasTo('toggle@example.com');
        });
    }

    public function test_no_email_sent_when_account_is_rejected(): void
    {
        Mail::fake();

        $user = User::create([
            'name' => 'Suspicious Kitchen',
            'email' => 'suspicious@example.com',
            'password' => bcrypt('password'),
            'role' => 'restaurant',
            'status' => 'pending',
            'phone' => '9876543211',
            'address' => '999 Unknown Ave',
            'city' => 'Metro',
            'state' => 'State',
            'pincode' => '100009',
        ]);

        $restaurant = Restaurant::create([
            'user_id' => $user->id,
            'restaurant_name' => 'Suspicious Kitchen',
            'owner_name' => 'Fake Owner',
            'phone' => '9876543211',
            'email' => 'suspicious@example.com',
            'address' => '999 Unknown Ave',
            'city' => 'Metro',
            'state' => 'State',
            'pincode' => '100009',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->post("/admin/restaurants/{$restaurant->id}/approve", [
            'status' => 'rejected',
        ]);

        $response->assertRedirect();
        $this->assertEquals('rejected', $restaurant->fresh()->status);
        $this->assertEquals('rejected', $user->fresh()->status);

        Mail::assertNothingOutgoing();
        $this->assertDatabaseMissing('notifications', [
            'user_id' => $user->id,
            'title' => 'Registration Approved',
        ]);
    }

    public function test_no_duplicate_email_when_admin_approves_already_active_user(): void
    {
        Mail::fake();

        $user = User::create([
            'name' => 'Double Click Diner',
            'email' => 'double@example.com',
            'password' => bcrypt('password'),
            'role' => 'restaurant',
            'status' => 'pending',
            'phone' => '9876543212',
            'address' => '505 Quick Ave',
            'city' => 'Metro',
            'state' => 'State',
            'pincode' => '100005',
        ]);

        $restaurant = Restaurant::create([
            'user_id' => $user->id,
            'restaurant_name' => 'Double Click Diner',
            'owner_name' => 'Fast Clicker',
            'phone' => '9876543212',
            'email' => 'double@example.com',
            'address' => '505 Quick Ave',
            'city' => 'Metro',
            'state' => 'State',
            'pincode' => '100005',
            'status' => 'pending',
        ]);

        // First approval
        $this->actingAs($this->admin)->post("/admin/restaurants/{$restaurant->id}/approve", [
            'status' => 'active',
        ]);

        // Second approval attempt on already-active restaurant
        $this->actingAs($this->admin)->post("/admin/restaurants/{$restaurant->id}/approve", [
            'status' => 'active',
        ]);

        Mail::assertSent(AccountApprovedMail::class, 1);
        $this->assertEquals(1, Notification::where('user_id', $user->id)->where('title', 'Registration Approved')->count());
    }

    public function test_email_content_contains_required_wording_and_login_url(): void
    {
        $user = User::create([
            'name' => 'Test Restaurant Owner',
            'email' => 'owner@testrestaurant.com',
            'password' => bcrypt('password'),
            'role' => 'restaurant',
            'status' => 'pending',
            'phone' => '9876543210',
            'address' => '123 Test St',
            'city' => 'City',
            'state' => 'State',
            'pincode' => '100001',
        ]);

        $mailable = new AccountApprovedMail($user);

        // 1. Check Subject
        $mailable->assertHasSubject('Your FoodWasteRescue Account Has Been Approved!');

        // 2. Check HTML Body
        $renderedHtml = $mailable->render();
        $this->assertStringContainsString('Hello Test Restaurant Owner,', $renderedHtml);
        $this->assertStringContainsString('Good news! Your FoodWasteRescue account has been approved by the admin. You can now log in to your account and use the platform.', $renderedHtml);
        $this->assertStringContainsString('Your account type:', $renderedHtml);
        $this->assertStringContainsString('Restaurant', $renderedHtml);
        $this->assertStringContainsString(route('login'), $renderedHtml);
        $this->assertStringContainsString('Thank you for joining FoodWasteRescue!', $renderedHtml);
        $this->assertStringContainsString('FoodWasteRescue Team', $renderedHtml);
    }

    public function test_account_stays_approved_if_email_delivery_fails(): void
    {
        Log::shouldReceive('error')->atLeast()->once();
        Log::shouldReceive('info')->zeroOrMoreTimes();
        Log::shouldReceive('warning')->zeroOrMoreTimes();

        Mail::shouldReceive('to')
            ->once()
            ->andThrow(new \Exception('Connection refused by SMTP server'));

        $user = User::create([
            'name' => 'Failing Mail Restaurant',
            'email' => 'fail@example.com',
            'password' => bcrypt('password'),
            'role' => 'restaurant',
            'status' => 'pending',
            'phone' => '9876543213',
            'address' => '707 Fail St',
            'city' => 'Metro',
            'state' => 'State',
            'pincode' => '100007',
        ]);

        $restaurant = Restaurant::create([
            'user_id' => $user->id,
            'restaurant_name' => 'Failing Mail Restaurant',
            'owner_name' => 'SMTP Test',
            'phone' => '9876543213',
            'email' => 'fail@example.com',
            'address' => '707 Fail St',
            'city' => 'Metro',
            'state' => 'State',
            'pincode' => '100007',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->post("/admin/restaurants/{$restaurant->id}/approve", [
            'status' => 'active',
        ]);

        $response->assertRedirect();
        // Warning explaining email failure, not false success
        $response->assertSessionHas('warning');
        $this->assertStringContainsString('Email notification failed', session('warning'));

        // Account remains approved in database
        $this->assertEquals('active', $restaurant->fresh()->status);
        $this->assertEquals('active', $user->fresh()->status);
    }
}
