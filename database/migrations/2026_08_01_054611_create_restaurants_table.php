<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('restaurants', function (Blueprint $table) {
            // Primary key
            $table->id();

            // Foreign key linking to the users table
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('Reference to the owner user account in users table');

            // Restaurant details
            $table->string('restaurant_name')->comment('Name of the restaurant');
            $table->string('owner_name')->comment('Name of the restaurant owner or primary contact');
            $table->string('license_number')->unique()->nullable()->comment('Food business safety license number');
            $table->string('gst_number')->unique()->nullable()->comment('GST registration number');

            // Contact details
            $table->string('phone')->comment('Restaurant contact phone number');
            $table->string('email')->unique()->comment('Official contact email address');

            // Location details
            $table->text('address')->comment('Full street address of the restaurant');
            $table->string('city')->comment('City of operation');
            $table->string('state')->comment('State/Province');
            $table->string('pincode', 10)->comment('Postal code/ZIP code');

            // Geolocation coordinates (crucial for location-based donation discovery)
            $table->decimal('latitude', 10, 8)->nullable()->comment('GPS latitude coordinate');
            $table->decimal('longitude', 11, 8)->nullable()->comment('GPS longitude coordinate');

            // Operating Hours
            $table->time('opening_time')->nullable()->comment('Opening hours');
            $table->time('closing_time')->nullable()->comment('Closing hours');

            // Branding and metadata
            $table->string('logo')->nullable()->comment('File path/URL of the restaurant logo');
            $table->text('description')->nullable()->comment('Brief bio or description of the restaurant');

            // Status of the restaurant profile
            $table->enum('status', ['active', 'inactive', 'pending', 'rejected'])
                ->default('pending')
                ->comment('Profile status: active (visible/verified), inactive (blocked/suspended), pending (waiting for admin review), rejected (rejected by admin)');

            // Standard timestamps
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restaurants');
    }
};
