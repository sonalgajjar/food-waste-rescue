<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Carbon\Carbon;

class FoodDonation extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'restaurant_id',
        'food_name',
        'category',
        'food_type',
        'quantity',
        'unit',
        'description',
        'cooked_time',
        'expiry_time',
        'pickup_address',
        'latitude',
        'longitude',
        'image',
        'status',
        'allergen_info',
        'storage_instructions',
        'packaging_condition',
        'pickup_instructions',
        'food_safety_declaration',
        'safety_declared_at',
        'safety_declared_by_user_id',
        'is_veg',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'float',
            'latitude' => 'float',
            'longitude' => 'float',
            'cooked_time' => 'datetime',
            'expiry_time' => 'datetime',
            'safety_declared_at' => 'datetime',
            'food_safety_declaration' => 'boolean',
            'is_veg' => 'boolean',
        ];
    }

    /**
     * Interact with the food donation's name to ensure no unwanted # number is attached.
     */
    protected function foodName(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value !== null ? trim(preg_replace('/\s*#\s*\d+\b/u', '', $value)) : $value,
            set: fn (?string $value) => $value !== null ? trim(preg_replace('/\s*#\s*\d+\b/u', '', $value)) : $value,
        );
    }

    /**
     * Check if the food donation is expiring soon (within 3 hours).
     */
    public function getIsExpiringSoonAttribute(): bool
    {
        if (!$this->expiry_time) {
            return false;
        }

        return $this->expiry_time->isFuture() && $this->expiry_time->diffInHours(now()) <= 3;
    }

    /**
     * Calculate Haversine distance in kilometres from given coordinates.
     */
    public function calculateDistanceTo(?float $lat, ?float $lon): ?float
    {
        if ($lat === null || $lon === null || $this->latitude === null || $this->longitude === null) {
            return null;
        }

        $earthRadius = 6371; // Earth radius in kilometres
        $latFrom = deg2rad($lat);
        $lonFrom = deg2rad($lon);
        $latTo = deg2rad($this->latitude);
        $lonTo = deg2rad($this->longitude);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return round($angle * $earthRadius, 1);
    }

    /**
     * Accessor for distance in KM when selected in query.
     */
    public function getDistanceKmAttribute(): ?float
    {
        if (isset($this->attributes['distance'])) {
            return round((float)$this->attributes['distance'], 1);
        }

        return null;
    }

    /**
     * Scope for currently available donations (not expired and available).
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available')
            ->where('expiry_time', '>', Carbon::now());
    }

    /**
     * Get the restaurant that donated this food item.
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * Get the pickup requests requested for this food donation.
     */
    public function pickupRequests(): HasMany
    {
        return $this->hasMany(PickupRequest::class);
    }

    /**
     * Get the active pickup request for this donation if any.
     */
    public function activePickupRequest(): HasOne
    {
        return $this->hasOne(PickupRequest::class)->whereIn('status', ['pending', 'accepted', 'picked']);
    }
}
