<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NGO extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'n_g_o_s';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'ngo_name',
        'registration_number',
        'owner_name',
        'phone',
        'email',
        'address',
        'city',
        'state',
        'pincode',
        'latitude',
        'longitude',
        'description',
        'logo',
        'status',
        'registration_document',
        'registration_expires_at',
        'fcra_number',
        'rejection_reason',
        'verification_notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'registration_expires_at' => 'date',
        ];
    }

    public function isRegistrationExpired(): bool
    {
        return $this->registration_expires_at && $this->registration_expires_at->isPast();
    }

    /**
     * Get the user account associated with this NGO.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the pickup requests requested by this NGO.
     */
    public function pickupRequests(): HasMany
    {
        return $this->hasMany(PickupRequest::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(DonationCertificate::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(DonationReview::class)->where('status', 'approved');
    }

    public function getAverageRatingAttribute(): float
    {
        $avg = $this->reviews()->where('reviewer_role', 'restaurant')->avg('rating');
        return $avg ? round((float) $avg, 1) : 0.0;
    }

    public function getReviewsCountAttribute(): int
    {
        return $this->reviews()->where('reviewer_role', 'restaurant')->count();
    }
}
