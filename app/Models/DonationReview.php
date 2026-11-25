<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DonationReview extends Model
{
    protected $fillable = [
        'pickup_request_id',
        'food_donation_id',
        'reviewer_id',
        'reviewer_role',
        'restaurant_id',
        'ngo_id',
        'rating',
        'criteria',
        'review',
        'status',
    ];

    protected $casts = [
        'rating' => 'integer',
        'criteria' => 'array',
    ];

    public function pickupRequest(): BelongsTo
    {
        return $this->belongsTo(PickupRequest::class);
    }

    public function foodDonation(): BelongsTo
    {
        return $this->belongsTo(FoodDonation::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function ngo(): BelongsTo
    {
        return $this->belongsTo(NGO::class);
    }
}
