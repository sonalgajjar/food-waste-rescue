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
        Schema::create('notifications', function (Blueprint $table) {
            // Primary key
            $table->id();

            // Foreign key linking to the users table
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('Reference to the recipient user in users table');

            // Notification content
            $table->string('title')->comment('Notification short title or action type');
            $table->text('message')->comment('Detailed body text of the notification');

            // Read state status
            $table->boolean('is_read')
                ->default(false)
                ->comment('Flag indicating if the user has seen/read the notification');

            // Standard timestamps
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
