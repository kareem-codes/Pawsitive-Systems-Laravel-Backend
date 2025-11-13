<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Models\AuditLog;

class InvoiceObserver
{
    /**
     * Handle the Invoice "created" event.
     */
    public function created(Invoice $invoice): void
    {
        AuditLog::log(
            model: $invoice,
            event: 'created',
            newValues: $invoice->toArray()
        );
    }

    /**
     * Handle the Invoice "updated" event.
     */
    public function updated(Invoice $invoice): void
    {
        $changes = $invoice->getChanges();
        
        // Add special logging for payment status changes
        if (isset($changes['status'])) {
            $description = sprintf(
                'Invoice status changed from %s to %s',
                $invoice->getOriginal('status'),
                $changes['status']
            );
        }

        AuditLog::log(
            model: $invoice,
            event: 'updated',
            oldValues: $invoice->getOriginal(),
            newValues: $changes
        );
    }

    /**
     * Handle the Invoice "deleted" event.
     */
    public function deleted(Invoice $invoice): void
    {
        AuditLog::log(
            model: $invoice,
            event: 'deleted',
            oldValues: $invoice->toArray()
        );
    }

    /**
     * Handle the Invoice "restored" event.
     */
    public function restored(Invoice $invoice): void
    {
        AuditLog::log(
            model: $invoice,
            event: 'restored',
            newValues: $invoice->toArray()
        );
    }

    /**
     * Handle the Invoice "force deleted" event.
     */
    public function forceDeleted(Invoice $invoice): void
    {
        AuditLog::log(
            model: $invoice,
            event: 'force_deleted',
            oldValues: $invoice->toArray()
        );
    }
}
