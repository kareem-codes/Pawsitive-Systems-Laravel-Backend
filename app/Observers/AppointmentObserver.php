<?php

namespace App\Observers;

use App\Models\Appointment;
use App\Models\AuditLog;

class AppointmentObserver
{
    /**
     * Handle the Appointment "created" event.
     */
    public function created(Appointment $appointment): void
    {
        AuditLog::log(
            model: $appointment,
            event: 'created',
            newValues: $appointment->toArray()
        );
    }

    /**
     * Handle the Appointment "updated" event.
     */
    public function updated(Appointment $appointment): void
    {
        $changes = $appointment->getChanges();
        
        // Add special logging for status changes
        if (isset($changes['status'])) {
            $description = sprintf(
                'Appointment status changed from %s to %s',
                $appointment->getOriginal('status'),
                $changes['status']
            );
        }

        AuditLog::log(
            model: $appointment,
            event: 'updated',
            oldValues: $appointment->getOriginal(),
            newValues: $changes
        );
    }

    /**
     * Handle the Appointment "deleted" event.
     */
    public function deleted(Appointment $appointment): void
    {
        AuditLog::log(
            model: $appointment,
            event: 'deleted',
            oldValues: $appointment->toArray()
        );
    }

    /**
     * Handle the Appointment "restored" event.
     */
    public function restored(Appointment $appointment): void
    {
        AuditLog::log(
            model: $appointment,
            event: 'restored',
            newValues: $appointment->toArray()
        );
    }

    /**
     * Handle the Appointment "force deleted" event.
     */
    public function forceDeleted(Appointment $appointment): void
    {
        AuditLog::log(
            model: $appointment,
            event: 'force_deleted',
            oldValues: $appointment->toArray()
        );
    }
}
