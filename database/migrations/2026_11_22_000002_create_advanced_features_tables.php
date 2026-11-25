<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Upgrade pickup_requests status column for extended lifecycle states
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `pickup_requests` MODIFY COLUMN `status` VARCHAR(50) NOT NULL DEFAULT 'pending'");
            DB::statement("ALTER TABLE `food_donations` MODIFY COLUMN `status` VARCHAR(50) NOT NULL DEFAULT 'available'");
        }

        // 2. Pickup Verifications (OTP and QR Code handover verification)
        Schema::create('pickup_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pickup_request_id')->constrained('pickup_requests')->cascadeOnDelete();
            $table->foreignId('food_donation_id')->constrained('food_donations')->cascadeOnDelete();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->foreignId('ngo_id')->constrained('n_g_o_s')->cascadeOnDelete();
            $table->foreignId('volunteer_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('otp_code', 255)->nullable();
            $table->string('otp_hash', 255)->nullable();
            $table->text('otp_encrypted')->nullable();
            $table->string('qr_token', 64)->nullable()->unique();
            $table->dateTime('otp_expires_at');
            $table->dateTime('resend_available_at')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->string('verification_method', 20)->nullable(); // 'otp' or 'qr'
            $table->dateTime('verified_at')->nullable();
            $table->string('status', 30)->default('pending'); // 'pending', 'verified', 'expired', 'failed'
            $table->timestamps();

            $table->index(['pickup_request_id', 'status']);
        });

        // 3. Pickup Messages (In-app pickup coordination chat)
        Schema::create('pickup_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pickup_request_id')->constrained('pickup_requests')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            $table->index(['pickup_request_id', 'created_at']);
        });

        // 4. Donation Certificates (Downloadable PDF certificates & verification)
        Schema::create('donation_certificates', function (Blueprint $table) {
            $table->id();
            $table->string('certificate_number', 64)->unique();
            $table->string('verification_token', 64)->unique();
            $table->foreignId('pickup_request_id')->constrained('pickup_requests')->cascadeOnDelete();
            $table->foreignId('food_donation_id')->constrained('food_donations')->cascadeOnDelete();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->foreignId('ngo_id')->constrained('n_g_o_s')->cascadeOnDelete();
            $table->decimal('meals_count', 8, 2)->default(0);
            $table->dateTime('issued_at');
            $table->string('pdf_path', 255)->nullable();
            $table->timestamps();
        });

        // 5. Donation Reviews (Two-way ratings and reviews)
        Schema::create('donation_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pickup_request_id')->constrained('pickup_requests')->cascadeOnDelete();
            $table->foreignId('food_donation_id')->constrained('food_donations')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->string('reviewer_role', 30); // 'restaurant' or 'ngo'
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->foreignId('ngo_id')->constrained('n_g_o_s')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating'); // 1-5
            $table->json('criteria')->nullable(); // criteria scores
            $table->text('review')->nullable();
            $table->string('status', 30)->default('approved'); // 'approved', 'hidden'
            $table->timestamps();

            $table->unique(['pickup_request_id', 'reviewer_role'], 'uniq_pickup_reviewer');
        });

        // 6. Donation Status History (Progress stepper timeline tracking)
        Schema::create('donation_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pickup_request_id')->constrained('pickup_requests')->cascadeOnDelete();
            $table->foreignId('food_donation_id')->constrained('food_donations')->cascadeOnDelete();
            $table->string('status', 50);
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['pickup_request_id', 'created_at']);
        });

        // 7. Recurring Donations (Scheduled donations for restaurants/banquet halls)
        Schema::create('recurring_donations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->string('schedule_name', 255);
            $table->string('food_name', 255);
            $table->string('category', 100);
            $table->string('food_type', 100);
            $table->decimal('quantity', 8, 2);
            $table->string('unit', 50);
            $table->text('description')->nullable();
            $table->text('pickup_address');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('frequency', 30)->default('daily'); // 'daily', 'weekly', 'selected_days'
            $table->json('selected_days')->nullable(); // e.g. ["Monday", "Wednesday"]
            $table->time('pickup_time_of_day'); // e.g. '22:30:00'
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->dateTime('last_generated_at')->nullable();
            $table->timestamps();

            $table->index(['restaurant_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_donations');
        Schema::dropIfExists('donation_status_histories');
        Schema::dropIfExists('donation_reviews');
        Schema::dropIfExists('donation_certificates');
        Schema::dropIfExists('pickup_messages');
        Schema::dropIfExists('pickup_verifications');
    }
};
