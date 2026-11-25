<?php

namespace App\Policies;

use App\Models\FoodDonation;
use App\Models\User;

class FoodDonationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, FoodDonation $foodDonation): bool
    {
        if ($user->role === 'admin' || $user->role === 'ngo') {
            return true;
        }

        if ($user->role === 'restaurant' && $foodDonation->restaurant) {
            return (int)$user->id === (int)$foodDonation->restaurant->user_id;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role === 'restaurant' && $user->status === 'active';
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, FoodDonation $foodDonation): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'restaurant' && $user->status === 'active' && $foodDonation->restaurant) {
            return (int)$user->id === (int)$foodDonation->restaurant->user_id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, FoodDonation $foodDonation): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'restaurant' && $user->status === 'active' && $foodDonation->restaurant) {
            return (int)$user->id === (int)$foodDonation->restaurant->user_id;
        }

        return false;
    }
}
