<?php

namespace App\Jobs;

use App\Models\Expense;
use App\Models\RecurringExpense;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class TransactRecurringExpenseJob implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        RecurringExpense::query()
            ->where('next_transaction_at', '<=', now())
            ->each(function (RecurringExpense $recurringExpense) {
                DB::transaction(function () use ($recurringExpense) {
                    $this->processRecurringExpense($recurringExpense);
                });
            });
    }

    private function processRecurringExpense(RecurringExpense $recurringExpense): void
    {
        if ($recurringExpense->account_id !== null) {
            $expense = Expense::create([
                'user_id' => $recurringExpense->user_id,
                'person_id' => $recurringExpense->person_id,
                'account_id' => $recurringExpense->account_id,
                'description' => $recurringExpense->description,
                'amount' => $recurringExpense->amount,
                'transacted_at' => $recurringExpense->next_transaction_at,
            ]);

            $expense->tags()->sync($recurringExpense->tags->pluck('id'));
        }

        if ($recurringExpense->remaining_recurrences !== null) {
            $recurringExpense->remaining_recurrences--;

            if ($recurringExpense->remaining_recurrences <= 0) {
                $recurringExpense->delete();

                return;
            }
        }

        $recurringExpense->next_transaction_at = $recurringExpense->frequency->addToDate(
            $recurringExpense->next_transaction_at
        );
        $recurringExpense->save();
    }
}
