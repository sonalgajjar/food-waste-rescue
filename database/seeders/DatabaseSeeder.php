<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Restaurant;
use App\Models\NGO;
use App\Models\FoodDonation;
use App\Models\PickupRequest;
use App\Models\Contact;
use App\Models\Notification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Admin User
        $adminUser = User::create([
            'name' => 'System Administrator',
            'email' => 'admin@foodrescue.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'phone' => '+1 800-555-0199',
            'address' => '100 Rescue Way, Admin Tower',
            'city' => 'Metropolis',
            'state' => 'State Central',
            'pincode' => '100001',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        // 2. Create 5 Restaurants
        $restaurantData = [
            [
                'name' => 'Chef Marco',
                'email' => 'marco@spicegarden.com',
                'rest_name' => 'The Spice Garden Restaurant',
                'license' => 'FSSAI-1092837411',
                'gst' => 'GSTIN27AABCU9603R1ZM',
                'phone' => '+1 555-0141',
                'address' => '45 Gourmet Avenue, Downtown',
                'city' => 'Metropolis',
                'state' => 'Central',
                'pincode' => '100002',
                'lat' => 28.6139,
                'lng' => 77.2090,
                'open' => '09:00:00',
                'close' => '23:00:00',
                'desc' => 'Premium dining establishment serving authentic continental and Indian delicacies.',
                'status' => 'active',
            ],
            [
                'name' => 'Sarah Jenkins',
                'email' => 'sarah@greenleafbistro.com',
                'rest_name' => 'Green Leaf Organic Bistro',
                'license' => 'FSSAI-1092837422',
                'gst' => 'GSTIN27AABCU9603R2ZN',
                'phone' => '+1 555-0142',
                'address' => '12 Eco Park Road, Suburb West',
                'city' => 'Metropolis',
                'state' => 'Central',
                'pincode' => '100003',
                'lat' => 28.6280,
                'lng' => 77.2195,
                'open' => '08:00:00',
                'close' => '22:00:00',
                'desc' => 'Farm-to-table organic eatery dedicated to zero-waste cuisine and fresh salads.',
                'status' => 'active',
            ],
            [
                'name' => 'Rajesh Sharma',
                'email' => 'rajesh@royalpalace.com',
                'rest_name' => 'Royal Palace Banquet Hall',
                'license' => 'FSSAI-1092837433',
                'gst' => 'GSTIN27AABCU9603R3ZO',
                'phone' => '+1 555-0143',
                'address' => '88 Celebration Plaza, East Wing',
                'city' => 'Metropolis',
                'state' => 'Central',
                'pincode' => '100004',
                'lat' => 28.6350,
                'lng' => 77.2250,
                'open' => '10:00:00',
                'close' => '01:00:00',
                'desc' => 'Large scale event caterer and banquet provider specializing in festive feasts.',
                'status' => 'active',
            ],
            [
                'name' => 'Anita Desai',
                'email' => 'anita@sunshinebakery.com',
                'rest_name' => 'Sunshine Artisanal Bakery',
                'license' => 'FSSAI-1092837444',
                'gst' => 'GSTIN27AABCU9603R4ZP',
                'phone' => '+1 555-0144',
                'address' => '23 Baker Street, Old Market',
                'city' => 'Metropolis',
                'state' => 'Central',
                'pincode' => '100005',
                'lat' => 28.6010,
                'lng' => 77.1980,
                'open' => '07:00:00',
                'close' => '20:00:00',
                'desc' => 'Freshly baked breads, pastries, sandwiches, and artisanal savories daily.',
                'status' => 'active',
            ],
            [
                'name' => 'David Miller',
                'email' => 'david@citycafediner.com',
                'rest_name' => 'City Cafe & Diner',
                'license' => 'FSSAI-1092837455',
                'gst' => 'GSTIN27AABCU9603R5ZQ',
                'phone' => '+1 555-0145',
                'address' => '101 Station Boulevard',
                'city' => 'Metropolis',
                'state' => 'Central',
                'pincode' => '100006',
                'lat' => 28.5920,
                'lng' => 77.2310,
                'open' => '06:00:00',
                'close' => '23:30:00',
                'desc' => 'Fast casual diner offering burgers, rice bowls, pasta, and fresh beverages.',
                'status' => 'pending',
            ],
        ];

        $restaurants = [];
        foreach ($restaurantData as $rd) {
            $user = User::create([
                'name' => $rd['name'],
                'email' => $rd['email'],
                'password' => Hash::make('password'),
                'role' => 'restaurant',
                'phone' => $rd['phone'],
                'address' => $rd['address'],
                'city' => $rd['city'],
                'state' => $rd['state'],
                'pincode' => $rd['pincode'],
                'latitude' => $rd['lat'],
                'longitude' => $rd['lng'],
                'status' => $rd['status'],
                'email_verified_at' => now(),
            ]);

            $rest = Restaurant::create([
                'user_id' => $user->id,
                'restaurant_name' => $rd['rest_name'],
                'owner_name' => $rd['name'],
                'license_number' => $rd['license'],
                'gst_number' => $rd['gst'],
                'phone' => $rd['phone'],
                'email' => $rd['email'],
                'address' => $rd['address'],
                'city' => $rd['city'],
                'state' => $rd['state'],
                'pincode' => $rd['pincode'],
                'latitude' => $rd['lat'],
                'longitude' => $rd['lng'],
                'opening_time' => $rd['open'],
                'closing_time' => $rd['close'],
                'description' => $rd['desc'],
                'status' => $rd['status'],
            ]);

            $restaurants[] = $rest;
        }

        // 3. Create 5 NGOs
        $ngoData = [
            [
                'name' => 'Dr. Robert Chen',
                'email' => 'info@hopefoodbank.org',
                'ngo_name' => 'Hope Food Bank Foundation',
                'reg_no' => 'NGO-REG-98701',
                'phone' => '+1 555-0201',
                'address' => '77 Compassion Road, Sector 4',
                'city' => 'Metropolis',
                'state' => 'Central',
                'pincode' => '100010',
                'lat' => 28.6180,
                'lng' => 77.2110,
                'desc' => 'Distributing surplus meals to local homeless shelters and community kitchens.',
                'status' => 'active',
            ],
            [
                'name' => 'Maria Santos',
                'email' => 'contact@feedtheneed.org',
                'ngo_name' => 'Feed The Needy Alliance',
                'reg_no' => 'NGO-REG-98702',
                'phone' => '+1 555-0202',
                'address' => '15 Mercy Street, North District',
                'city' => 'Metropolis',
                'state' => 'Central',
                'pincode' => '100011',
                'lat' => 28.6250,
                'lng' => 77.2050,
                'desc' => 'Grassroots youth organization ensuring no child goes to bed hungry.',
                'status' => 'active',
            ],
            [
                'name' => 'Amitabh Varma',
                'email' => 'support@careandshare.org',
                'ngo_name' => 'Care & Share Society',
                'reg_no' => 'NGO-REG-98703',
                'phone' => '+1 555-0203',
                'address' => '30 Kindness Lane, East Gate',
                'city' => 'Metropolis',
                'state' => 'Central',
                'pincode' => '100012',
                'lat' => 28.6080,
                'lng' => 77.2220,
                'desc' => 'Providing daily nutritional support to senior citizens and disabled individuals.',
                'status' => 'active',
            ],
            [
                'name' => 'Emily Watson',
                'email' => 'hello@urbanharvest.org',
                'ngo_name' => 'Urban Harvest Relief Network',
                'reg_no' => 'NGO-REG-98704',
                'phone' => '+1 555-0204',
                'address' => '54 Solidarity Square',
                'city' => 'Metropolis',
                'state' => 'Central',
                'pincode' => '100013',
                'lat' => 28.6310,
                'lng' => 77.1950,
                'desc' => 'Connecting commercial kitchens with community food pantries dynamically.',
                'status' => 'active',
            ],
            [
                'name' => 'Vikram Patel',
                'email' => 'admin@zeroHunger.org',
                'ngo_name' => 'Zero Hunger Initiative',
                'reg_no' => 'NGO-REG-98705',
                'phone' => '+1 555-0205',
                'address' => '90 Harmony Enclave',
                'city' => 'Metropolis',
                'state' => 'Central',
                'pincode' => '100014',
                'lat' => 28.5990,
                'lng' => 77.2150,
                'desc' => 'Dedicated to eliminating urban food waste through immediate logistics.',
                'status' => 'pending',
            ],
        ];

        $ngos = [];
        foreach ($ngoData as $nd) {
            $user = User::create([
                'name' => $nd['name'],
                'email' => $nd['email'],
                'password' => Hash::make('password'),
                'role' => 'ngo',
                'phone' => $nd['phone'],
                'address' => $nd['address'],
                'city' => $nd['city'],
                'state' => $nd['state'],
                'pincode' => $nd['pincode'],
                'latitude' => $nd['lat'],
                'longitude' => $nd['lng'],
                'status' => $nd['status'],
                'email_verified_at' => now(),
            ]);

            $ngo = NGO::create([
                'user_id' => $user->id,
                'ngo_name' => $nd['ngo_name'],
                'registration_number' => $nd['reg_no'],
                'owner_name' => $nd['name'],
                'phone' => $nd['phone'],
                'email' => $nd['email'],
                'address' => $nd['address'],
                'city' => $nd['city'],
                'state' => $nd['state'],
                'pincode' => $nd['pincode'],
                'latitude' => $nd['lat'],
                'longitude' => $nd['lng'],
                'description' => $nd['desc'],
                'status' => $nd['status'],
            ]);

            $ngos[] = $ngo;
        }

        // 4. Create 30 Food Donations
        $categories = ['Veg', 'Non-Veg', 'Vegan', 'Dairy', 'Bakery', 'Groceries'];
        $types = ['Cooked Meals', 'Packaged Goods', 'Raw Ingredients', 'Fresh Produce', 'Baked Goods'];
        $units = ['servings', 'kg', 'boxes', 'packets', 'plates'];
        $statuses = ['available', 'requested', 'approved', 'picked', 'completed', 'expired'];

        $foodTemplates = [
            'Surplus Biryani Meals', 'Fresh Vegetable Curry & Rice', 'Assorted Whole Grain Breads',
            'Grilled Chicken Meal Trays', 'Paneer Butter Masala Packs', 'Mixed Fruit Baskets',
            'Freshly Baked Croissants', 'Lentil Soup Bowls', 'Steamed Dumplings Container',
            'Pasta Alfredo Trays', 'Sandwich Combo Boxes', 'Greek Salad Containers',
            'Muffin & Pastry Boxes', 'Roasted Vegetables Pack', 'Dairy Milk & Yogurt Tubs'
        ];

        $donations = [];
        for ($i = 1; $i <= 30; $i++) {
            $restaurant = $restaurants[($i - 1) % count($restaurants)];
            $foodName = $foodTemplates[($i - 1) % count($foodTemplates)];
            $status = $statuses[($i - 1) % count($statuses)];
            $cookedTime = Carbon::now()->subHours(rand(1, 12));
            $expiryTime = ($status === 'expired') 
                ? Carbon::now()->subHours(rand(1, 5)) 
                : Carbon::now()->addHours(rand(3, 24));

            $donation = FoodDonation::create([
                'restaurant_id' => $restaurant->id,
                'food_name' => $foodName,
                'category' => $categories[rand(0, count($categories) - 1)],
                'food_type' => $types[rand(0, count($types) - 1)],
                'quantity' => rand(10, 150),
                'unit' => $units[rand(0, count($units) - 1)],
                'description' => 'Hygenically packaged surplus food ready for immediate consumption/redistribution.',
                'cooked_time' => $cookedTime,
                'expiry_time' => $expiryTime,
                'pickup_address' => $restaurant->address,
                'latitude' => $restaurant->latitude,
                'longitude' => $restaurant->longitude,
                'status' => $status,
            ]);

            $donations[] = $donation;
        }

        // 5. Create 20 Pickup Requests
        $requestStatuses = ['pending', 'accepted', 'rejected', 'completed'];
        for ($j = 1; $j <= 20; $j++) {
            $donation = $donations[($j - 1) % count($donations)];
            $ngo = $ngos[($j - 1) % count($ngos)];
            $reqStatus = $requestStatuses[($j - 1) % count($requestStatuses)];

            PickupRequest::create([
                'food_donation_id' => $donation->id,
                'ngo_id' => $ngo->id,
                'pickup_time' => Carbon::now()->addHours(rand(1, 6)),
                'status' => $reqStatus,
                'remarks' => 'Volunteer pickup vehicle dispatched for immediate collection.',
            ]);
        }

        // 6. Create 10 Contact Messages
        $contactSubjects = [
            'Partnership Inquiry', 'Volunteer Opportunity', 'Technical Support',
            'Food Safety Query', 'Registration Assistance', 'Sponsorship Details',
            'Feature Request', 'Feedback on App', 'Donation Limits', 'Regional Expansion'
        ];

        for ($c = 1; $c <= 10; $c++) {
            Contact::create([
                'name' => 'Visitor User #' . $c,
                'email' => 'visitor' . $c . '@example.com',
                'subject' => $contactSubjects[$c - 1],
                'message' => 'Hello team, I am interested in knowing more about how your food waste rescue system works.',
                'status' => ($c % 2 === 0) ? 'replied' : 'pending',
            ]);
        }

        // 7. Create Demo Notifications
        Notification::create([
            'user_id' => $restaurants[0]->user_id,
            'title' => 'New Pickup Request',
            'message' => 'Hope Food Bank Foundation has requested pickup for Surplus Biryani Meals.',
            'is_read' => false,
        ]);

        Notification::create([
            'user_id' => $ngos[0]->user_id,
            'title' => 'Pickup Request Approved',
            'message' => 'The Spice Garden Restaurant has approved your pickup request.',
            'is_read' => false,
        ]);

        Notification::create([
            'user_id' => $adminUser->id,
            'title' => 'New Restaurant Registration',
            'message' => 'City Cafe & Diner has registered and is pending approval.',
            'is_read' => false,
        ]);
    }
}
