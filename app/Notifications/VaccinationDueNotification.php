<?php

namespace App\Notifications;

use App\Models\Vaccination;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VaccinationDueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $vaccination;

    /**
     * Create a new notification instance.
     */
    public function __construct(Vaccination $vaccination)
    {
        $this->vaccination = $vaccination;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $petName = $this->vaccination->pet->name;
        $vaccineName = $this->vaccination->vaccine_name;
        $dueDate = $this->vaccination->next_due_date->format('F j, Y');
        $daysUntilDue = now()->diffInDays($this->vaccination->next_due_date, false);

        $subject = $daysUntilDue < 0 
            ? "Overdue Vaccination for {$petName}" 
            : "Upcoming Vaccination Reminder for {$petName}";

        $message = new MailMessage;
        $message->subject($subject)
            ->greeting("Hello {$notifiable->name}!")
            ->line("This is a reminder about an upcoming vaccination for your pet.");

        $message->line("**Pet:** {$petName}")
            ->line("**Vaccine:** {$vaccineName}")
            ->line("**Due Date:** {$dueDate}");

        if ($daysUntilDue < 0) {
            $message->line("⚠️ **This vaccination is now overdue by " . abs($daysUntilDue) . " days.**");
        } elseif ($daysUntilDue <= 7) {
            $message->line("⚡ **This vaccination is due in {$daysUntilDue} days.**");
        } else {
            $message->line("📅 This vaccination is due in {$daysUntilDue} days.");
        }

        $message->action('Schedule Appointment', url('/appointments/create'))
            ->line('Please contact us to schedule an appointment for this vaccination.')
            ->line('Keeping your pet\'s vaccinations up to date is important for their health and safety.');

        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'vaccination_id' => $this->vaccination->id,
            'pet_id' => $this->vaccination->pet_id,
            'pet_name' => $this->vaccination->pet->name,
            'vaccine_name' => $this->vaccination->vaccine_name,
            'next_due_date' => $this->vaccination->next_due_date->toDateString(),
            'days_until_due' => now()->diffInDays($this->vaccination->next_due_date, false),
            'message' => "{$this->vaccination->vaccine_name} vaccination due for {$this->vaccination->pet->name}"
        ];
    }
}