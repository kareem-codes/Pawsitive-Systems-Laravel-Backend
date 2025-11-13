<?php

namespace App\Policies;

use App\Models\Pet;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PetPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view pets');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Pet $pet): bool
    {
        // Owners can only view their own pets
        if ($user->isOwner()) {
            return $pet->user_id === $user->id;
        }

        // Staff can view all pets if they have permission
        return $user->can('view pets');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Owners can create pets for themselves, staff need permission
        if ($user->isOwner()) {
            return true;
        }
        
        return $user->can('create pets');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Pet $pet): bool
    {
        // Owners can only edit their own pets
        if ($user->isOwner()) {
            return $pet->user_id === $user->id;
        }

        return $user->can('edit pets');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Pet $pet): bool
    {
        // Owners cannot delete pets, only staff can
        if ($user->isOwner()) {
            return false;
        }

        return $user->can('delete pets');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Pet $pet): bool
    {
        return $user->can('delete pets');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Pet $pet): bool
    {
        return $user->can('delete pets');
    }
}
