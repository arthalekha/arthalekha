<?php

namespace App\Jobs;

use App\Features\DailyTransactionReminder;
use App\Models\User;
use App\Notifications\DailyTransactionReminderNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Laravel\Pennant\Feature;

class SendDailyTransactionRemindersJob implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        User::query()
            ->each(function (User $user) {
                if (Feature::for($user)->active(DailyTransactionReminder::class)) {
                    $user->notify(new DailyTransactionReminderNotification);
                }
            });
    }
}
