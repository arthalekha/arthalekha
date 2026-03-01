<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RecurringTransactionPendingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Pending Recurring Transactions')
            ->line('You have recurring transactions that need your attention.')
            ->line('These items are due but do not have an account assigned.')
            ->action('Review Pending Transactions', route('recurring-transactions.dashboard'))
            ->line('You can record them with an account or skip to the next occurrence.');
    }
}
