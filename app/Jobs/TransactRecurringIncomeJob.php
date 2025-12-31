<?php

namespace App\Jobs;

use App\Models\Income;
use App\Models\RecurringIncome;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class TransactRecurringIncomeJob implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        RecurringIncome::query()
            ->where('next_transaction_at', '<=', now())
            ->each(function (RecurringIncome $recurringIncome) {
                DB::transaction(function () use ($recurringIncome) {
                    $this->processRecurringIncome($recurringIncome);
                });
            });
    }

    private function processRecurringIncome(RecurringIncome $recurringIncome): void
    {
        $income = Income::create([
            'user_id' => $recurringIncome->user_id,
            'person_id' => $recurringIncome->person_id,
            'account_id' => $recurringIncome->account_id,
            'description' => $recurringIncome->description,
            'amount' => $recurringIncome->amount,
            'transacted_at' => $recurringIncome->next_transaction_at,
        ]);

        $income->tags()->sync($recurringIncome->tags->pluck('id'));

        if ($recurringIncome->remaining_recurrences !== null) {
            $recurringIncome->remaining_recurrences--;

            if ($recurringIncome->remaining_recurrences <= 0) {
                $recurringIncome->delete();

                return;
            }
        }

        $recurringIncome->next_transaction_at = $recurringIncome->frequency->addToDate(
            $recurringIncome->next_transaction_at
        );
        $recurringIncome->save();
    }
}
