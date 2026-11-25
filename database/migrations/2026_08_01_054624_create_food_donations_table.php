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
        Schema::create('food_donations', function (Blueprint $table) {
            // Primary key
            $table->id();

            // Foreign key linking to the restaurants table
            $table->foreignId('restaurant_id')
                ->constrained('restaurants')
                ->cascadeOnDelete()
                ->comment('Reference to the donating restaurant in restaurants table');

            // Food details
            $table->string('food_name')->comment('Name or title of the food item(s)');
            $table->string('category')->comment('Category of food (e.g., Veg, Non-Veg, Vegan, Dairy, Bakery, Groceries)');
            $table->string('food_type')->comment('Type of food preparation (e.g., Cooked, Packaged, Raw, Fresh Produce)');
            $table->decimal('quantity', 8, 2)->comment('Quantity of food available for rescue');
            $table->string('unit', 50)->comment('Unit of measurement (e.g., servings, kg, packets, litres, plates)');
            $table->text('description')->nullable()->comment('Detailed description of food ingredients, allergens, or quantity specifics');

            // Food freshness details
            $table->dateTime('cooked_time')->nullable()->comment('Date and time when the food was cooked/prepared');
            $table->dateTime('expiry_time')->comment('Date and time when the food will expire/become unsafe to consume');

            // Pickup location details (can be different from the restaurant\'s main address)
            $table->text('pickup_address')->comment('Full address where the food should be picked up');
            $table->decimal('latitude', 10, 8)->nullable()->comment('GPS latitude of the pickup location');
            $table->decimal('longitude', 11, 8)->nullable()->comment('GPS longitude of the pickup location');

            // Metadata and images
            $table->string('image')->nullable()->comment('File path/URL of the food item photo');

            // Status of the donation
            $table->enum('status', ['available', 'requested', 'approved', 'picked', 'completed', 'expired'])
                ->default('available')
                ->comment('Donation lifecycle state: available (listed), requested (NGO claimed), approved (donor agreed), picked (NGO collected), completed (distributed), expired (passed expiry time)');

            // Standard timestamps
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('food_donations');
    }
};
