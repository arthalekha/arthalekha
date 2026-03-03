<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\RecurringTransactionPendingNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendRecurringTransactionRemindersJob implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        User::query()
            ->where(function ($query) {
                $query->whereHas('recurringIncomes', function ($q) {
                    $q->where('next_transaction_at', '<=', now())
                        ->whereNull('account_id');
                })->orWhereHas('recurringExpenses', function ($q) {
                    $q->where('next_transaction_at', '<=', now())
                        ->whereNull('account_id');
                });
            })
            ->each(function (User $user) {
                $user->notify(new RecurringTransactionPendingNotification);
            });
    }
}
