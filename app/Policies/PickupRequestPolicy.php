<?php

namespace App\Policies;

use App\Models\PickupRequest;
use App\Models\User;

class PickupRequestPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PickupRequest $pickupRequest): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'ngo' && $user->ngo) {
            return (int)$user->ngo->id === (int)$pickupRequest->ngo_id;
        }

        if ($user->role === 'restaurant' && $user->restaurant && $pickupRequest->foodDonation) {
            return (int)$user->restaurant->id === (int)$pickupRequest->foodDonation->restaurant_id;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role === 'ngo' && $user->status === 'active';
    }

    /**
     * Determine whether the user can update/manage the request.
     */
    public function update(User $user, PickupRequest $pickupRequest): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        // Restaurant can update requests on its donations
        if ($user->role === 'restaurant' && $user->restaurant && $pickupRequest->foodDonation) {
            return (int)$user->restaurant->id === (int)$pickupRequest->foodDonation->restaurant_id;
        }

        // NGO can update its own request (e.g. marking as picked/completed)
        if ($user->role === 'ngo' && $user->ngo) {
            return (int)$user->ngo->id === (int)$pickupRequest->ngo_id;
        }

        return false;
    }

    /**
     * Determine whether the user can cancel the request.
     */
    public function cancel(User $user, PickupRequest $pickupRequest): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'ngo' && $user->ngo) {
            return (int)$user->ngo->id === (int)$pickupRequest->ngo_id && $pickupRequest->status === 'pending';
        }

        return false;
    }
}
