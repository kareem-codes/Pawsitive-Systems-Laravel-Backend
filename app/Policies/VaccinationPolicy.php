<?php

namespace App\Policies;

use App\Models\Vaccination;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class VaccinationPolicy
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
    public function view(User $user, Vaccination $vaccination): bool
    {
        // Owners can only view vaccinations for their own pets
        if ($user->isOwner()) {
            return $vaccination->pet->user_id === $user->id;
        }
        
        // Staff can view all vaccinations
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only veterinarians can create vaccination records
        return $user->hasRole(['veterinarian', 'admin']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Vaccination $vaccination): bool
    {
        // Only veterinarians can update vaccination records
        return $user->hasRole(['veterinarian', 'admin']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Vaccination $vaccination): bool
    {
        // Only vets and admins can delete vaccination records
        return $user->hasRole(['veterinarian', 'admin']);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Vaccination $vaccination): bool
    {
        return $user->hasRole(['admin']);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Vaccination $vaccination): bool
    {
        return $user->hasRole(['admin']);
    }
}
