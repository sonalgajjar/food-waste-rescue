<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PickupVerification extends Model
{
    protected $fillable = [
        'pickup_request_id',
        'food_donation_id',
        'restaurant_id',
        'ngo_id',
        'volunteer_id',
        'otp_code',
        'otp_hash',
        'otp_encrypted',
        'qr_token',
        'qr_token_encrypted',
        'otp_expires_at',
        'resend_available_at',
        'attempts',
        'verification_method',
        'verified_at',
        'status',
    ];

    protected $casts = [
        'otp_expires_at' => 'datetime',
        'resend_available_at' => 'datetime',
        'verified_at' => 'datetime',
        'attempts' => 'integer',
    ];

    public function pickupRequest(): BelongsTo
    {
        return $this->belongsTo(PickupRequest::class);
    }

    public function foodDonation(): BelongsTo
    {
        return $this->belongsTo(FoodDonation::class);
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function ngo(): BelongsTo
    {
        return $this->belongsTo(NGO::class);
    }

    public function volunteer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'volunteer_id');
    }

    public function isExpired(): bool
    {
        return $this->otp_expires_at && $this->otp_expires_at->isPast();
    }

    /**
     * Safely decrypt OTP for authorized restaurant display.
     */
    public function getDecryptedOtp(): ?string
    {
        if ($this->isVerified() || $this->isExpired()) {
            return null;
        }

        if ($this->otp_encrypted) {
            try {
                return \Illuminate\Support\Facades\Crypt::decryptString($this->otp_encrypted);
            } catch (\Throwable $e) {
                return null;
            }
        }

        return null;
    }

    /**
     * Safely decrypt raw QR token for authorized donor screen QR code rendering.
     */
    public function getDecryptedQrToken(): ?string
    {
        if ($this->isVerified() || $this->isExpired()) {
            return null;
        }

        if ($this->qr_token_encrypted) {
            try {
                return \Illuminate\Support\Facades\Crypt::decryptString($this->qr_token_encrypted);
            } catch (\Throwable $e) {
                return null;
            }
        }

        return $this->qr_token;
    }

    public function isVerified(): bool
    {
        return $this->status === 'verified' && $this->verified_at !== null;
    }
}
