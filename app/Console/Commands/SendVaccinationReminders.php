<?php

namespace App\Console\Commands;

use App\Models\Vaccination;
use App\Notifications\VaccinationDueNotification;
use Illuminate\Console\Command;

class SendVaccinationReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vaccinations:send-reminders {--days=7 : Number of days ahead to check for due vaccinations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send vaccination reminder notifications to pet owners for upcoming or overdue vaccinations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        
        // Get vaccinations due within the specified days or overdue
        $vaccinations = Vaccination::with(['pet.owner'])
            ->whereNotNull('next_due_date')
            ->where('next_due_date', '<=', now()->addDays($days))
            ->get();

        $sentCount = 0;

        foreach ($vaccinations as $vaccination) {
            if ($vaccination->pet && $vaccination->pet->owner && $vaccination->pet->owner->email) {
                $vaccination->pet->owner->notify(new VaccinationDueNotification($vaccination));
                $sentCount++;
            }
        }

        $this->info("Sent {$sentCount} vaccination reminder(s).");

        return Command::SUCCESS;
    }
}
