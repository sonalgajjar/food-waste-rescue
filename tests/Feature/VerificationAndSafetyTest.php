<?php

namespace Tests\Feature;

use App\Models\FoodDonation;
use App\Models\NGO;
use App\Models\Notification;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VerificationAndSafetyTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'System Admin',
            'email' => 'admin@foodrescue.org',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'status' => 'active',
            'phone' => '9999999999',
            'address' => 'Admin HQ',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'pincode' => '110001',
        ]);
    }

    public function test_restaurant_can_register_with_license_document_and_expiry(): void
    {
        Storage::fake('local');
        $file = UploadedFile::fake()->create('fssai_cert.pdf', 500, 'application/pdf');

        $response = $this->post('/register', [
            'role' => 'restaurant',
            'name' => 'Chef Suresh',
            'email' => 'suresh@restaurant.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '9876543210',
            'address' => '12 Connaught Place',
            'city' => 'New Delhi',
            'state' => 'Delhi',
            'pincode' => '110001',
            'restaurant_name' => 'Royal Tandoor',
            'owner_name' => 'Chef Suresh',
            'license_number' => 'FSSAI-DELHI-9988',
            'license_expires_at' => '2028-12-31',
            'license_document' => $file,
            'opening_time' => '10:00',
            'closing_time' => '23:00',
        ]);

        $response->assertRedirect('/login');

        $restaurant = Restaurant::where('license_number', 'FSSAI-DELHI-9988')->first();
        $this->assertNotNull($restaurant);
        $this->assertNotNull($restaurant->license_document);
        $this->assertEquals('2028-12-31', $restaurant->license_expires_at->format('Y-m-d'));
        Storage::disk('local')->assertExists($restaurant->license_document);
    }

    public function test_ngo_can_register_with_registration_document_and_fcra(): void
    {
        Storage::fake('local');
        $file = UploadedFile::fake()->create('ngo_cert.pdf', 350, 'application/pdf');

        $response = $this->post('/register', [
            'role' => 'ngo',
            'name' => 'Asha Foundation Rep',
            'email' => 'contact@ashafoundation.org',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '9811223344',
            'address' => '45 Seva Marg',
            'city' => 'New Delhi',
            'state' => 'Delhi',
            'pincode' => '110002',
            'ngo_name' => 'Asha Food Mission',
            'registration_number' => 'NGO-DEL-2024-55',
            'fcra_number' => 'FCRA-IN-987654',
            'registration_document' => $file,
        ]);

        $response->assertRedirect('/login');

        $ngo = NGO::where('registration_number', 'NGO-DEL-2024-55')->first();
        $this->assertNotNull($ngo);
        $this->assertNotNull($ngo->registration_document);
        $this->assertEquals('FCRA-IN-987654', $ngo->fcra_number);
        Storage::disk('local')->assertExists($ngo->registration_document);
    }

    public function test_admin_can_preview_uploaded_documents(): void
    {
        Storage::fake('local');
        $docPath = Storage::disk('local')->putFile('documents/licenses', UploadedFile::fake()->create('fssai.pdf', 100));

        $user = User::create([
            'name' => 'Partner Donor',
            'email' => 'donor@test.com',
            'password' => bcrypt('password'),
            'role' => 'restaurant',
            'status' => 'pending',
            'phone' => '9876543210',
            'address' => 'Street',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'pincode' => '110001',
        ]);

        $restaurant = Restaurant::create([
            'user_id' => $user->id,
            'restaurant_name' => 'Test Kitchen',
            'owner_name' => 'Partner Donor',
            'license_number' => 'FSSAI-001122',
            'license_document' => $docPath,
            'phone' => '9876543210',
            'email' => 'donor@test.com',
            'address' => 'Street',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'pincode' => '110001',
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin);

        $response = $this->get(route('admin.documents.view', ['type' => 'restaurant', 'id' => $restaurant->id]));
        $response->assertStatus(200);
    }

    public function test_admin_can_reject_restaurant_with_reason_and_notes(): void
    {
        $user = User::create([
            'name' => 'Reject Test',
            'email' => 'reject@rest.com',
            'password' => bcrypt('password'),
            'role' => 'restaurant',
            'status' => 'pending',
            'phone' => '9876543210',
            'address' => 'Street',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'pincode' => '110001',
        ]);

        $restaurant = Restaurant::create([
            'user_id' => $user->id,
            'restaurant_name' => 'Reject Kitchen',
            'owner_name' => 'Reject Test',
            'license_number' => 'FSSAI-REJECT-99',
            'phone' => '9876543210',
            'email' => 'reject@rest.com',
            'address' => 'Street',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'pincode' => '110001',
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin);

        $response = $this->post(route('admin.restaurants.approve', $restaurant->id), [
            'status' => 'rejected',
            'rejection_reason' => 'FSSAI license expired 6 months ago.',
            'verification_notes' => 'Audited on national portal, expired 2024.',
        ]);

        $response->assertSessionHas('success');

        $restaurant->refresh();
        $this->assertEquals('rejected', $restaurant->status);
        $this->assertEquals('FSSAI license expired 6 months ago.', $restaurant->rejection_reason);
        $this->assertEquals('Audited on national portal, expired 2024.', $restaurant->verification_notes);

        // Verify rejection notification was created for user
        $notification = Notification::where('user_id', $user->id)->first();
        $this->assertNotNull($notification);
        $this->assertStringContainsString('FSSAI license expired 6 months ago', $notification->message);
    }

    public function test_restaurant_can_create_donation_with_full_safety_details(): void
    {
        $user = User::create([
            'name' => 'Safety Chef',
            'email' => 'chef@safe.com',
            'password' => bcrypt('password'),
            'role' => 'restaurant',
            'status' => 'active',
            'phone' => '9876543210',
            'address' => 'Kitchen Road',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'pincode' => '110001',
        ]);

        $restaurant = Restaurant::create([
            'user_id' => $user->id,
            'restaurant_name' => 'Safe Dining Co',
            'owner_name' => 'Safety Chef',
            'license_number' => 'FSSAI-SAFE-77',
            'phone' => '9876543210',
            'email' => 'chef@safe.com',
            'address' => 'Kitchen Road',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'pincode' => '110001',
            'latitude' => 28.6139,
            'longitude' => 77.2090,
            'status' => 'active',
        ]);

        $this->actingAs($user);

        $response = $this->post(route('restaurant.donations.store'), [
            'food_name' => 'Paneer Butter Masala & Rotis',
            'category' => 'Veg',
            'food_type' => 'Cooked Meals',
            'quantity' => 45,
            'unit' => 'servings',
            'description' => 'Freshly cooked for lunch banquet, immediately chilled.',
            'cooked_time' => now()->format('Y-m-d H:i:s'),
            'expiry_time' => now()->addHours(8)->format('Y-m-d H:i:s'),
            'pickup_address' => 'Kitchen Road, Delhi',
            'latitude' => 28.6139,
            'longitude' => 77.2090,
            'allergen_info' => 'Contains Dairy (Milk & Butter), Gluten (Wheat)',
            'storage_instructions' => 'Keep Refrigerated (Below 5°C)',
            'packaging_condition' => 'Sealed Food-Grade Containers',
            'pickup_instructions' => 'Back door loading bay, ask for Chef',
            'food_safety_declaration' => 1,
        ]);

        $response->assertRedirect(route('restaurant.donations.index'));

        $donation = FoodDonation::where('food_name', 'Paneer Butter Masala & Rotis')->first();
        $this->assertNotNull($donation);
        $this->assertEquals('Keep Refrigerated (Below 5°C)', $donation->storage_instructions);
        $this->assertEquals('Sealed Food-Grade Containers', $donation->packaging_condition);
        $this->assertEquals('Contains Dairy (Milk & Butter), Gluten (Wheat)', $donation->allergen_info);
        $this->assertTrue($donation->food_safety_declaration);
    }
}
