<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PickupRequest extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'food_donation_id',
        'ngo_id',
        'pickup_time',
        'status',
        'remarks',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pickup_time' => 'datetime',
        ];
    }

    /**
     * Helper status checks.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }

    public function isOutForPickup(): bool
    {
        return $this->status === 'out_for_pickup';
    }

    public function isHandoverConfirmed(): bool
    {
        return in_array($this->status, ['handover_confirmed', 'picked']);
    }

    public function isPicked(): bool
    {
        return in_array($this->status, ['handover_confirmed', 'picked']);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isRejected(): bool
    {
        return in_array($this->status, ['rejected', 'cancelled']);
    }

    /**
     * Get the food donation associated with this request.
     */
    public function foodDonation(): BelongsTo
    {
        return $this->belongsTo(FoodDonation::class);
    }

    /**
     * Get the NGO that made this pickup request.
     */
    public function ngo(): BelongsTo
    {
        return $this->belongsTo(NGO::class);
    }

    public function verification(): HasOne
    {
        return $this->hasOne(PickupVerification::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(PickupMessage::class);
    }

    public function certificate(): HasOne
    {
        return $this->hasOne(DonationCertificate::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(DonationReview::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(DonationStatusHistory::class);
    }
}
