<?php

namespace App\Observers;

use App\Models\MedicalRecord;
use App\Models\AuditLog;

class MedicalRecordObserver
{
    /**
     * Handle the MedicalRecord "created" event.
     */
    public function created(MedicalRecord $medicalRecord): void
    {
        AuditLog::log(
            model: $medicalRecord,
            event: 'created',
            newValues: $medicalRecord->toArray()
        );
    }

    /**
     * Handle the MedicalRecord "updated" event.
     */
    public function updated(MedicalRecord $medicalRecord): void
    {
        AuditLog::log(
            model: $medicalRecord,
            event: 'updated',
            oldValues: $medicalRecord->getOriginal(),
            newValues: $medicalRecord->getChanges()
        );
    }

    /**
     * Handle the MedicalRecord "deleted" event.
     */
    public function deleted(MedicalRecord $medicalRecord): void
    {
        AuditLog::log(
            model: $medicalRecord,
            event: 'deleted',
            oldValues: $medicalRecord->toArray()
        );
    }

    /**
     * Handle the MedicalRecord "restored" event.
     */
    public function restored(MedicalRecord $medicalRecord): void
    {
        AuditLog::log(
            model: $medicalRecord,
            event: 'restored',
            newValues: $medicalRecord->toArray()
        );
    }

    /**
     * Handle the MedicalRecord "force deleted" event.
     */
    public function forceDeleted(MedicalRecord $medicalRecord): void
    {
        AuditLog::log(
            model: $medicalRecord,
            event: 'force_deleted',
            oldValues: $medicalRecord->toArray()
        );
    }
}
