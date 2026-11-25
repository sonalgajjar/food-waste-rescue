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
        if (Schema::hasTable('pickup_verifications')) {
            Schema::table('pickup_verifications', function (Blueprint $table) {
                if (!Schema::hasColumn('pickup_verifications', 'qr_token_encrypted')) {
                    $table->text('qr_token_encrypted')->nullable()->after('qr_token')->comment('AES-256 encrypted QR raw token for authorized donor display');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('pickup_verifications')) {
            Schema::table('pickup_verifications', function (Blueprint $table) {
                if (Schema::hasColumn('pickup_verifications', 'qr_token_encrypted')) {
                    $table->dropColumn('qr_token_encrypted');
                }
            });
        }
    }
};
