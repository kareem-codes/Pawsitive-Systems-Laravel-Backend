<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $appointment;

    /**
     * Create a new notification instance.
     */
    public function __construct(Appointment $appointment)
    {
        $this->appointment = $appointment;
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
        $appointmentDate = $this->appointment->appointment_date->format('l, F j, Y');
        $appointmentTime = $this->appointment->appointment_date;
        $petName = $this->appointment->pet->name;
        $vetName = $this->appointment->veterinarian->name ?? 'your veterinarian';

        return (new MailMessage)
            ->subject('Appointment Reminder - Tomorrow')
            ->greeting("Hello {$notifiable->name}!")
            ->line("This is a friendly reminder about your upcoming appointment.")
            ->line("**Pet:** {$petName}")
            ->line("**Date:** {$appointmentDate}")
            ->line("**Time:** {$appointmentTime}")
            ->line("**Veterinarian:** Dr. {$vetName}")
            ->line("**Reason:** {$this->appointment->reason}")
            ->action('View Appointment Details', url('/appointments/' . $this->appointment->id))
            ->line('Please arrive 10 minutes early to complete any necessary paperwork.')
            ->line('If you need to reschedule, please contact us as soon as possible.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'appointment_id' => $this->appointment->id,
            'pet_name' => $this->appointment->pet->name,
            'appointment_date' => $this->appointment->appointment_date->toDateString(),
            'appointment_date' => $this->appointment->appointment_date,
            'reason' => $this->appointment->reason,
            'message' => "Appointment reminder for {$this->appointment->pet->name} tomorrow"
        ];
    }
}