<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Restaurant extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'restaurant_name',
        'owner_name',
        'license_number',
        'gst_number',
        'phone',
        'email',
        'address',
        'city',
        'state',
        'pincode',
        'latitude',
        'longitude',
        'opening_time',
        'closing_time',
        'logo',
        'description',
        'status',
        'license_document',
        'license_expires_at',
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
            'opening_time' => 'datetime:H:i:s',
            'closing_time' => 'datetime:H:i:s',
            'license_expires_at' => 'date',
        ];
    }

    public function isLicenseExpired(): bool
    {
        return $this->license_expires_at && $this->license_expires_at->isPast();
    }

    /**
     * Get the user account associated with this restaurant.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the food donations listed by this restaurant.
     */
    public function foodDonations(): HasMany
    {
        return $this->hasMany(FoodDonation::class);
    }

    public function recurringDonations(): HasMany
    {
        return $this->hasMany(RecurringDonation::class);
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
        $avg = $this->reviews()->where('reviewer_role', 'ngo')->avg('rating');
        return $avg ? round((float) $avg, 1) : 0.0;
    }

    public function getReviewsCountAttribute(): int
    {
        return $this->reviews()->where('reviewer_role', 'ngo')->count();
    }
}
