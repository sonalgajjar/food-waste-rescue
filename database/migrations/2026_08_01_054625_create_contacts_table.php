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
        Schema::create('contacts', function (Blueprint $table) {
            // Primary key
            $table->id();

            // Contact details
            $table->string('name')->comment('Name of the person initiating contact');
            $table->string('email')->comment('Email address of the sender');
            $table->string('subject')->comment('Subject line of the contact message');
            $table->text('message')->comment('Main text content of the contact message');

            // Status of the message handling
            $table->enum('status', ['pending', 'read', 'replied'])
                ->default('pending')
                ->comment('Status of contact message: pending (received), read (opened by admin), replied (responded to sender)');

            // Standard timestamps
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
