<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `restaurants` MODIFY COLUMN `status` ENUM('active', 'inactive', 'pending', 'rejected') NOT NULL DEFAULT 'pending'");
            DB::statement("ALTER TABLE `n_g_o_s` MODIFY COLUMN `status` ENUM('active', 'inactive', 'pending', 'rejected') NOT NULL DEFAULT 'pending'");
            DB::statement("ALTER TABLE `users` MODIFY COLUMN `status` ENUM('active', 'inactive', 'pending', 'rejected') NOT NULL DEFAULT 'active'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `restaurants` MODIFY COLUMN `status` ENUM('active', 'inactive', 'pending') NOT NULL DEFAULT 'pending'");
            DB::statement("ALTER TABLE `n_g_o_s` MODIFY COLUMN `status` ENUM('active', 'inactive', 'pending') NOT NULL DEFAULT 'pending'");
            DB::statement("ALTER TABLE `users` MODIFY COLUMN `status` ENUM('active', 'inactive', 'pending') NOT NULL DEFAULT 'active'");
        }
    }
};
