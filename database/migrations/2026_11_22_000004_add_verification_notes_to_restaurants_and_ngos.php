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
        if (Schema::hasTable('restaurants') && !Schema::hasColumn('restaurants', 'verification_notes')) {
            Schema::table('restaurants', function (Blueprint $table) {
                $table->text('verification_notes')->nullable()->after('rejection_reason')->comment('Internal admin audit notes');
            });
        }

        if (Schema::hasTable('n_g_o_s') && !Schema::hasColumn('n_g_o_s', 'verification_notes')) {
            Schema::table('n_g_o_s', function (Blueprint $table) {
                $table->text('verification_notes')->nullable()->after('rejection_reason')->comment('Internal admin audit notes');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('restaurants') && Schema::hasColumn('restaurants', 'verification_notes')) {
            Schema::table('restaurants', function (Blueprint $table) {
                $table->dropColumn('verification_notes');
            });
        }

        if (Schema::hasTable('n_g_o_s') && Schema::hasColumn('n_g_o_s', 'verification_notes')) {
            Schema::table('n_g_o_s', function (Blueprint $table) {
                $table->dropColumn('verification_notes');
            });
        }
    }
};
