<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringDonation extends Model
{
    protected $fillable = [
        'restaurant_id',
        'schedule_name',
        'food_name',
        'category',
        'food_type',
        'quantity',
        'unit',
        'description',
        'pickup_address',
        'latitude',
        'longitude',
        'frequency',
        'selected_days',
        'pickup_time_of_day',
        'start_date',
        'end_date',
        'is_active',
        'last_generated_at',
    ];

    protected $casts = [
        'quantity' => 'float',
        'latitude' => 'float',
        'longitude' => 'float',
        'selected_days' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'last_generated_at' => 'datetime',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}
