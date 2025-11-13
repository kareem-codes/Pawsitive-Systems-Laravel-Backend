<?php

namespace App\Observers;

use App\Models\Pet;
use App\Models\AuditLog;

class PetObserver
{
    /**
     * Handle the Pet "created" event.
     */
    public function created(Pet $pet): void
    {
        AuditLog::log(
            model: $pet,
            event: 'created',
            newValues: $pet->toArray()
        );
    }

    /**
     * Handle the Pet "updated" event.
     */
    public function updated(Pet $pet): void
    {
        AuditLog::log(
            model: $pet,
            event: 'updated',
            oldValues: $pet->getOriginal(),
            newValues: $pet->getChanges()
        );
    }

    /**
     * Handle the Pet "deleted" event.
     */
    public function deleted(Pet $pet): void
    {
        AuditLog::log(
            model: $pet,
            event: 'deleted',
            oldValues: $pet->toArray()
        );
    }

    /**
     * Handle the Pet "restored" event.
     */
    public function restored(Pet $pet): void
    {
        AuditLog::log(
            model: $pet,
            event: 'restored',
            newValues: $pet->toArray()
        );
    }

    /**
     * Handle the Pet "force deleted" event.
     */
    public function forceDeleted(Pet $pet): void
    {
        AuditLog::log(
            model: $pet,
            event: 'force_deleted',
            oldValues: $pet->toArray()
        );
    }
}
