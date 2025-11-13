<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AppointmentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view appointments
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Appointment $appointment): bool
    {
        // Owners can only view their own appointments
        if ($user->isOwner()) {
            return $appointment->user_id === $user->id;
        }
        
        // Staff can view all appointments
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Owners, receptionists, and vets can create appointments
        return $user->isOwner() || 
               $user->hasRole(['receptionist', 'veterinarian', 'admin']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Appointment $appointment): bool
    {
        // Owners can update their own appointments
        if ($user->isOwner()) {
            return $appointment->user_id === $user->id;
        }
        
        // Receptionists, vets, and admins can update any appointment
        return $user->hasRole(['receptionist', 'veterinarian', 'admin']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Appointment $appointment): bool
    {
        // Only receptionists and admins can delete appointments
        return $user->hasRole(['receptionist', 'admin']);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Appointment $appointment): bool
    {
        return $user->hasRole(['admin']);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Appointment $appointment): bool
    {
        return $user->hasRole(['admin']);
    }
}
