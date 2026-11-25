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
        // 1. Food safety and handling fields for food_donations
        Schema::table('food_donations', function (Blueprint $table) {
            $table->text('allergen_info')->nullable()->after('description')->comment('Known allergens (e.g., Dairy, Nuts, Gluten, Soy)');
            $table->string('storage_instructions', 255)->nullable()->after('allergen_info')->comment('Storage conditions (e.g., Keep refrigerated below 4C)');
            $table->string('packaging_condition', 255)->nullable()->after('storage_instructions')->comment('Packaging details (e.g., Sealed Food-Grade Containers)');
            $table->text('pickup_instructions')->nullable()->after('packaging_condition')->comment('Special pickup logistics (e.g., Bring insulated carriers)');
            $table->boolean('food_safety_declaration')->default(true)->after('pickup_instructions')->comment('Donor certification that food is hygienically prepared and untouched');
            $table->boolean('is_veg')->default(true)->after('category')->comment('Quick vegetarian flag');
        });

        // 2. Document upload and audit fields for restaurants
        Schema::table('restaurants', function (Blueprint $table) {
            $table->string('license_document', 255)->nullable()->after('license_number')->comment('Path to uploaded FSSAI certificate document');
            $table->date('license_expires_at')->nullable()->after('license_document')->comment('FSSAI certificate expiry date');
            $table->text('rejection_reason')->nullable()->after('status')->comment('Reason provided by admin if registration rejected');
            $table->text('verification_notes')->nullable()->after('rejection_reason')->comment('Internal admin audit notes');
        });

        // 3. Document upload and audit fields for NGOs
        Schema::table('n_g_o_s', function (Blueprint $table) {
            $table->string('registration_document', 255)->nullable()->after('registration_number')->comment('Path to uploaded 12A/80G/Registration certificate');
            $table->string('fcra_number', 100)->nullable()->after('registration_document')->comment('FCRA or 80G tax exemption number');
            $table->text('rejection_reason')->nullable()->after('status')->comment('Reason provided by admin if registration rejected');
            $table->text('verification_notes')->nullable()->after('rejection_reason')->comment('Internal admin audit notes');
        });

        // 4. Secure hashed OTP column in pickup_verifications
        if (Schema::hasTable('pickup_verifications') && !Schema::hasColumn('pickup_verifications', 'otp_hash')) {
            Schema::table('pickup_verifications', function (Blueprint $table) {
                $table->string('otp_hash', 100)->nullable()->after('otp_code')->comment('Secure cryptographic hash of the 6-digit OTP');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pickup_verifications', function (Blueprint $table) {
            $table->dropColumn(['otp_hash']);
        });

        Schema::table('n_g_o_s', function (Blueprint $table) {
            $table->dropColumn(['registration_document', 'fcra_number', 'rejection_reason', 'verification_notes']);
        });

        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn(['license_document', 'license_expires_at', 'rejection_reason', 'verification_notes']);
        });

        Schema::table('food_donations', function (Blueprint $table) {
            $table->dropColumn([
                'allergen_info',
                'storage_instructions',
                'packaging_condition',
                'pickup_instructions',
                'food_safety_declaration',
                'is_veg',
            ]);
        });
    }
};
