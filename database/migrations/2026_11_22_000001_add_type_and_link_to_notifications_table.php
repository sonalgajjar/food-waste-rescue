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
        Schema::table('notifications', function (Blueprint $table) {
            $table->string('type')->nullable()->default('general')->after('user_id')->comment('Notification category or event type (e.g., food_alert, pickup_request)');
            $table->string('link')->nullable()->after('message')->comment('Optional target link URL for direct redirection');
            $table->foreignId('food_donation_id')->nullable()->after('link')->constrained('food_donations')->nullOnDelete()->comment('Optional linked food donation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropForeign(['food_donation_id']);
            $table->dropColumn(['type', 'link', 'food_donation_id']);
        });
    }
};
