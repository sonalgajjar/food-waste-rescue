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
        // 1. Create Handover Audit Logs table (Immutable security audit trail)
        if (!Schema::hasTable('handover_audit_logs')) {
            Schema::create('handover_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pickup_request_id')->nullable()->constrained('pickup_requests')->nullOnDelete();
                $table->foreignId('food_donation_id')->nullable()->constrained('food_donations')->nullOnDelete();
                $table->foreignId('restaurant_id')->nullable()->constrained('restaurants')->nullOnDelete();
                $table->foreignId('ngo_id')->nullable()->constrained('n_g_o_s')->nullOnDelete();
                $table->foreignId('performed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                
                $table->string('action', 60)->comment('Action name e.g. otp_generated, otp_verified, qr_verified, etc.');
                $table->string('verification_method', 30)->nullable()->comment('otp, qr, admin_override');
                $table->string('status_before', 50)->nullable();
                $table->string('status_after', 50)->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->json('details')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['pickup_request_id', 'created_at']);
                $table->index(['action', 'created_at']);
            });
        }

        // 2. Add encrypted OTP column and make otp_code nullable in pickup_verifications
        if (Schema::hasTable('pickup_verifications')) {
            Schema::table('pickup_verifications', function (Blueprint $table) {
                if (!Schema::hasColumn('pickup_verifications', 'otp_encrypted')) {
                    $table->text('otp_encrypted')->nullable()->after('otp_hash')->comment('AES-256 encrypted OTP for secure authorized retrieval');
                }
                if (Schema::hasColumn('pickup_verifications', 'otp_code')) {
                    $table->string('otp_code', 255)->nullable()->change();
                }
            });
        }

        // 3. Add safety declaration audit fields to food_donations
        if (Schema::hasTable('food_donations')) {
            Schema::table('food_donations', function (Blueprint $table) {
                if (!Schema::hasColumn('food_donations', 'safety_declared_at')) {
                    $table->timestamp('safety_declared_at')->nullable()->after('food_safety_declaration');
                }
                if (!Schema::hasColumn('food_donations', 'safety_declared_by_user_id')) {
                    $table->foreignId('safety_declared_by_user_id')->nullable()->after('safety_declared_at')->constrained('users')->nullOnDelete();
                }
            });
        }

        // 4. Add registration expiry tracking to NGOs
        if (Schema::hasTable('n_g_o_s')) {
            Schema::table('n_g_o_s', function (Blueprint $table) {
                if (!Schema::hasColumn('n_g_o_s', 'registration_expires_at')) {
                    $table->date('registration_expires_at')->nullable()->after('registration_document')->comment('NGO Registration certificate expiry date');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('n_g_o_s') && Schema::hasColumn('n_g_o_s', 'registration_expires_at')) {
            Schema::table('n_g_o_s', function (Blueprint $table) {
                $table->dropColumn('registration_expires_at');
            });
        }

        if (Schema::hasTable('food_donations')) {
            Schema::table('food_donations', function (Blueprint $table) {
                $table->dropConstrainedForeignId('safety_declared_by_user_id');
                $table->dropColumn(['safety_declared_at']);
            });
        }

        if (Schema::hasTable('pickup_verifications') && Schema::hasColumn('pickup_verifications', 'otp_encrypted')) {
            Schema::table('pickup_verifications', function (Blueprint $table) {
                $table->dropColumn('otp_encrypted');
            });
        }

        Schema::dropIfExists('handover_audit_logs');
    }
};
