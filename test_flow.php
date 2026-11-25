<?php

use Illuminate\Http\Request;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Models\User;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$request = Request::create('/register', 'POST', [
    'role' => 'restaurant',
    'name' => 'Test Restaurant User',
    'email' => 'test' . rand(1000,9999) . '@example.com',
    'password' => 'password123',
    'password_confirmation' => 'password123',
    'phone' => '1234567890',
    'address' => 'Adajan',
    'city' => 'Surat',
    'state' => 'Gujarat',
    'pincode' => '395009',
    'restaurant_name' => 'Green Grill',
    'owner_name' => 'Test Owner',
    'license_number' => 'FSSAI-12345',
    'opening_time' => '09:00',
    'closing_time' => '22:00'
]);

$controller = new RegisteredUserController();
try {
    $response = $controller->store($request);
    echo "Registration successful!\n";
    
    // Check user and lat/lon
    $user = User::latest()->first();
    echo "User created with Lat: " . $user->latitude . " and Lon: " . $user->longitude . "\n";
    
    // Now test admin approval
    $adminController = new \App\Http\Controllers\Admin\AdminController();
    $adminRequest = Request::create('/admin/restaurants/' . $user->restaurant->id . '/approve', 'POST', [
        'status' => 'active'
    ]);
    
    $adminController->approveRestaurant($adminRequest, $user->restaurant->id);
    
    echo "Admin approval simulated.\n";
    
    // Check notifications
    $notif = \App\Models\Notification::where('user_id', $user->id)->first();
    if ($notif) {
        echo "Notification created successfully! Title: " . $notif->title . "\n";
    } else {
        echo "Notification failed to create.\n";
    }
    
} catch (\Illuminate\Validation\ValidationException $e) {
    echo "Validation failed:\n";
    print_r($e->errors());
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
