<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Pet;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\MedicalRecord;
use App\Models\Payment;
use App\Observers\PetObserver;
use App\Observers\AppointmentObserver;
use App\Observers\InvoiceObserver;
use App\Observers\MedicalRecordObserver;
use App\Observers\PaymentObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register model observers for automatic audit logging
        Pet::observe(PetObserver::class);
        Appointment::observe(AppointmentObserver::class);
        Invoice::observe(InvoiceObserver::class);
        MedicalRecord::observe(MedicalRecordObserver::class);
        Payment::observe(PaymentObserver::class);
    }
}
