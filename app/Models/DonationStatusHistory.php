<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DonationStatusHistory extends Model
{
    protected $fillable = [
        'pickup_request_id',
        'food_donation_id',
        'status',
        'changed_by_user_id',
        'notes',
    ];

    public function pickupRequest(): BelongsTo
    {
        return $this->belongsTo(PickupRequest::class);
    }

    public function foodDonation(): BelongsTo
    {
        return $this->belongsTo(FoodDonation::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}
