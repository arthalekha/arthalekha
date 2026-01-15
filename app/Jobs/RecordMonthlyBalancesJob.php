<?php

namespace App\Jobs;

use App\Models\Account;
use App\Models\Balance;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Date;

class RecordMonthlyBalancesJob implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     * Records the current balance of all accounts for the last day of the previous month.
     */
    public function handle(): void
    {
        $recordedUntil = Date::now()->subMonth()->endOfMonth()->toDateString();

        Account::query()
            ->whereDoesntHave('balances', function ($query) use ($recordedUntil) {
                $query->whereDate('recorded_until', $recordedUntil);
            })
            ->each(function (Account $account) use ($recordedUntil) {
                Balance::create([
                    'account_id' => $account->id,
                    'balance' => $account->current_balance,
                    'recorded_until' => $recordedUntil,
                ]);
            });
    }
}
