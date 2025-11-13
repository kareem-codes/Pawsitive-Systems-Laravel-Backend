<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Notifications\AppointmentReminderNotification;
use Illuminate\Console\Command;

class SendAppointmentReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'appointments:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send appointment reminder notifications to pet owners 24 hours before their appointment';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Get appointments scheduled for tomorrow
        $tomorrow = now()->addDay()->startOfDay();
        $dayAfter = now()->addDay()->endOfDay();

        $appointments = Appointment::with(['owner', 'pet', 'veterinarian'])
            ->where('status', 'scheduled')
            ->whereBetween('appointment_date', [$tomorrow, $dayAfter])
            ->get();

        $sentCount = 0;

        foreach ($appointments as $appointment) {
            if ($appointment->owner && $appointment->owner->email) {
                $appointment->owner->notify(new AppointmentReminderNotification($appointment));
                $sentCount++;
            }
        }

        $this->info("Sent {$sentCount} appointment reminder(s).");

        return Command::SUCCESS;
    }
}
