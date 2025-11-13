<?php

namespace App\Observers;

use App\Models\Payment;
use App\Models\AuditLog;

class PaymentObserver
{
    /**
     * Handle the Payment "created" event.
     */
    public function created(Payment $payment): void
    {
        AuditLog::log(
            model: $payment,
            event: 'created',
            newValues: $payment->toArray()
        );
    }

    /**
     * Handle the Payment "updated" event.
     */
    public function updated(Payment $payment): void
    {
        AuditLog::log(
            model: $payment,
            event: 'updated',
            oldValues: $payment->getOriginal(),
            newValues: $payment->getChanges()
        );
    }

    /**
     * Handle the Payment "deleted" event.
     */
    public function deleted(Payment $payment): void
    {
        AuditLog::log(
            model: $payment,
            event: 'deleted',
            oldValues: $payment->toArray()
        );
    }

    /**
     * Handle the Payment "restored" event.
     */
    public function restored(Payment $payment): void
    {
        AuditLog::log(
            model: $payment,
            event: 'restored',
            newValues: $payment->toArray()
        );
    }

    /**
     * Handle the Payment "force deleted" event.
     */
    public function forceDeleted(Payment $payment): void
    {
        AuditLog::log(
            model: $payment,
            event: 'force_deleted',
            oldValues: $payment->toArray()
        );
    }
}
