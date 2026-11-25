<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('pickup_verifications')) {
            // Direct MySQL alter if on MySQL, else Blueprint change
            if (DB::getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE `pickup_verifications` MODIFY `qr_token` VARCHAR(64) NULL');
            } else {
                Schema::table('pickup_verifications', function (Blueprint $table) {
                    $table->string('qr_token', 64)->nullable()->change();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('pickup_verifications')) {
            if (DB::getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE `pickup_verifications` MODIFY `qr_token` VARCHAR(64) NOT NULL');
            } else {
                Schema::table('pickup_verifications', function (Blueprint $table) {
                    $table->string('qr_token', 64)->nullable(false)->change();
                });
            }
        }
    }
};
