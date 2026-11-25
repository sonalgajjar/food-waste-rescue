<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HandoverAuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'pickup_request_id',
        'food_donation_id',
        'restaurant_id',
        'ngo_id',
        'performed_by_user_id',
        'action',
        'verification_method',
        'status_before',
        'status_after',
        'ip_address',
        'user_agent',
        'details',
        'created_at',
    ];

    protected $casts = [
        'details' => 'array',
        'created_at' => 'datetime',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by_user_id');
    }

    /**
     * Create an immutable audit log entry.
     */
    public static function log(
        string $action,
        PickupRequest $pickupRequest,
        ?User $user = null,
        ?string $method = null,
        ?string $statusBefore = null,
        ?string $statusAfter = null,
        array $details = []
    ): self {
        return self::create([
            'pickup_request_id' => $pickupRequest->id,
            'food_donation_id' => $pickupRequest->food_donation_id,
            'restaurant_id' => $pickupRequest->foodDonation?->restaurant_id,
            'ngo_id' => $pickupRequest->ngo_id,
            'performed_by_user_id' => $user?->id ?? auth()->id(),
            'action' => $action,
            'verification_method' => $method,
            'status_before' => $statusBefore,
            'status_after' => $statusAfter,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'details' => $details,
            'created_at' => now(),
        ]);
    }
}
