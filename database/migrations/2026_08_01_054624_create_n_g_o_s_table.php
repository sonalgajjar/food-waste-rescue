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
        Schema::create('n_g_o_s', function (Blueprint $table) {
            // Primary key
            $table->id();

            // Foreign key linking to the users table
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('Reference to the owner user account in users table');

            // NGO Details
            $table->string('ngo_name')->comment('Name of the NGO');
            $table->string('registration_number')->unique()->comment('Government registration or certificate number of the NGO');
            $table->string('owner_name')->comment('Name of the NGO representative or contact person');

            // Contact details
            $table->string('phone')->comment('Primary contact phone number of the NGO');
            $table->string('email')->unique()->comment('Official email address of the NGO');

            // Address and Location details
            $table->text('address')->comment('Full street address of the NGO office or warehouse');
            $table->string('city')->comment('City of operation');
            $table->string('state')->comment('State/Province');
            $table->string('pincode', 10)->comment('Postal code/ZIP code');

            // Geolocation coordinates (crucial for finding nearby rescue food donations)
            $table->decimal('latitude', 10, 8)->nullable()->comment('GPS latitude coordinate');
            $table->decimal('longitude', 11, 8)->nullable()->comment('GPS longitude coordinate');

            // Branding and metadata
            $table->text('description')->nullable()->comment('Brief bio, mission, or description of the NGO');
            $table->string('logo')->nullable()->comment('File path/URL of the NGO logo');

            // Status of the NGO registration profile
            $table->enum('status', ['active', 'inactive', 'pending', 'rejected'])
                ->default('pending')
                ->comment('NGO profile status: active (approved & operating), inactive (blocked/suspended), pending (waiting for admin verification), rejected (rejected by admin)');

            // Standard timestamps
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('n_g_o_s');
    }
};
