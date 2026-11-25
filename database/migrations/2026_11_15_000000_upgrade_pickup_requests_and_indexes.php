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
        // Modify pickup_requests status enum if MySQL
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `pickup_requests` MODIFY COLUMN `status` ENUM('pending', 'accepted', 'picked', 'completed', 'rejected') NOT NULL DEFAULT 'pending'");
        }

        // Add performance indexes if not already existing
        Schema::table('food_donations', function (Blueprint $table) {
            $table->index('status', 'idx_food_donations_status');
            $table->index('expiry_time', 'idx_food_donations_expiry_time');
            $table->index('category', 'idx_food_donations_category');
        });

        Schema::table('pickup_requests', function (Blueprint $table) {
            $table->index('status', 'idx_pickup_requests_status');
            $table->index(['food_donation_id', 'status'], 'idx_pickup_requests_donation_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pickup_requests', function (Blueprint $table) {
            $table->dropIndex('idx_pickup_requests_status');
            $table->dropIndex('idx_pickup_requests_donation_status');
        });

        Schema::table('food_donations', function (Blueprint $table) {
            $table->dropIndex('idx_food_donations_status');
            $table->dropIndex('idx_food_donations_expiry_time');
            $table->dropIndex('idx_food_donations_category');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `pickup_requests` MODIFY COLUMN `status` ENUM('pending', 'accepted', 'completed', 'rejected') NOT NULL DEFAULT 'pending'");
        }
    }
};
