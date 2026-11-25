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
        Schema::create('pickup_requests', function (Blueprint $table) {
            // Primary key
            $table->id();

            // Foreign keys linking to food_donations and n_g_o_s tables
            $table->foreignId('food_donation_id')
                ->constrained('food_donations')
                ->cascadeOnDelete()
                ->comment('Reference to the requested food donation in food_donations table');

            $table->foreignId('ngo_id')
                ->constrained('n_g_o_s')
                ->cascadeOnDelete()
                ->comment('Reference to the NGO making the pickup request');

            // Pickup details
            $table->dateTime('pickup_time')->nullable()->comment('Scheduled or actual time of food pickup');

            // Request status
            $table->enum('status', ['pending', 'accepted', 'out_for_pickup', 'handover_confirmed', 'picked', 'rejected', 'completed', 'cancelled'])
                ->default('pending')
                ->comment('Status of the request: pending (NGO requested), accepted (Restaurant approved), picked (NGO collected), rejected (Restaurant declined), completed (Food successfully distributed)');

            // Additional details/instructions
            $table->text('remarks')->nullable()->comment('Additional notes or instructions regarding the pickup (e.g., driver details, special instructions)');

            // Standard timestamps
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pickup_requests');
    }
};
