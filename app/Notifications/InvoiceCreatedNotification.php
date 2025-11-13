<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $invoice;

    /**
     * Create a new notification instance.
     */
    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
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
        $invoiceNumber = $this->invoice->invoice_number;
        $totalAmount = number_format($this->invoice->total_amount, 2);
        $dueDate = $this->invoice->due_date ? $this->invoice->due_date->format('F j, Y') : 'Upon receipt';
        $petName = $this->invoice->pet ? $this->invoice->pet->name : 'your pet';

        return (new MailMessage)
            ->subject("New Invoice #{$invoiceNumber}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("A new invoice has been generated for services provided to {$petName}.")
            ->line("**Invoice Number:** {$invoiceNumber}")
            ->line("**Total Amount:** \${$totalAmount}")
            ->line("**Due Date:** {$dueDate}")
            ->action('View Invoice', url('/invoices/' . $this->invoice->id))
            ->action('Download PDF', url('/api/v1/invoices/' . $this->invoice->id . '/pdf'))
            ->line('You can pay this invoice online or at our clinic.')
            ->line('Thank you for choosing Pawsitive Systems!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'total_amount' => $this->invoice->total_amount,
            'due_date' => $this->invoice->due_date?->toDateString(),
            'pet_name' => $this->invoice->pet?->name,
            'message' => "New invoice #{$this->invoice->invoice_number} for \${$this->invoice->total_amount}"
        ];
    }
}