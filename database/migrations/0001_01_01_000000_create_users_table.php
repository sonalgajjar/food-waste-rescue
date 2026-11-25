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
        Schema::create('users', function (Blueprint $table) {
            // Primary key
            $table->id();

            // Basic authentication details
            $table->string('name')->comment('Full name of the user or organization contact');
            $table->string('email')->unique()->comment('Primary email address for login and notifications');
            $table->timestamp('email_verified_at')->nullable()->comment('Timestamp when email was verified');
            $table->string('password')->comment('Hashed password');

            // Role management
            $table->enum('role', ['admin', 'restaurant', 'ngo'])
                ->comment('Access level role: admin (system administrator), restaurant (food donor), ngo (food collector)');

            // Contact & Profile details
            $table->string('phone')->nullable()->comment('Primary contact phone number');
            $table->string('profile_photo')->nullable()->comment('File path/URL of the user profile photo');

            // Address details
            $table->text('address')->nullable()->comment('Street address of the restaurant, NGO, or user');
            $table->string('city')->nullable()->comment('City of operation');
            $table->string('state')->nullable()->comment('State or province');
            $table->string('pincode', 10)->nullable()->comment('ZIP or Postal Code');

            // Geolocation coordinates (critical for finding nearby rescue food)
            $table->decimal('latitude', 10, 8)->nullable()->comment('GPS latitude coordinates');
            $table->decimal('longitude', 11, 8)->nullable()->comment('GPS longitude coordinates');

            // Account status
            $table->enum('status', ['active', 'inactive', 'pending', 'rejected'])
                ->default('active')
                ->comment('Current status of the account: active (can log in/operate), inactive (blocked/disabled), pending (awaiting admin approval), rejected (rejected by admin)');

            // Authentication session tracking & timestamps
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
