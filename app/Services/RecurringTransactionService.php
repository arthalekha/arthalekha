<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Income;
use App\Models\RecurringExpense;
use App\Models\RecurringIncome;
use Illuminate\Support\Facades\DB;

class RecurringTransactionService
{
    public function __construct(
        private IncomeService $incomeService,
        private ExpenseService $expenseService,
    ) {}

    /**
     * Record an income from a recurring income item.
     *
     * @param  array<string, mixed>  $data
     */
    public function recordIncome(RecurringIncome $recurringIncome, array $data): Income
    {
        return DB::transaction(function () use ($recurringIncome, $data) {
            $income = $this->incomeService->createIncome($recurringIncome->user, [
                'person_id' => $recurringIncome->person_id,
                'account_id' => $data['account_id'],
                'description' => $recurringIncome->description,
                'amount' => $recurringIncome->amount,
                'transacted_at' => $data['transacted_at'],
                'tags' => $recurringIncome->tags->pluck('id')->toArray(),
            ]);

            $this->advanceOrDelete($recurringIncome);

            return $income;
        });
    }

    /**
     * Record an expense from a recurring expense item.
     *
     * @param  array<string, mixed>  $data
     */
    public function recordExpense(RecurringExpense $recurringExpense, array $data): Expense
    {
        return DB::transaction(function () use ($recurringExpense, $data) {
            $expense = $this->expenseService->createExpense($recurringExpense->user, [
                'person_id' => $recurringExpense->person_id,
                'account_id' => $data['account_id'],
                'description' => $recurringExpense->description,
                'amount' => $recurringExpense->amount,
                'transacted_at' => $data['transacted_at'],
                'tags' => $recurringExpense->tags->pluck('id')->toArray(),
            ]);

            $this->advanceOrDelete($recurringExpense);

            return $expense;
        });
    }

    /**
     * Skip a recurring income occurrence.
     */
    public function skipIncome(RecurringIncome $recurringIncome): void
    {
        $this->advanceOrDelete($recurringIncome);
    }

    /**
     * Skip a recurring expense occurrence.
     */
    public function skipExpense(RecurringExpense $recurringExpense): void
    {
        $this->advanceOrDelete($recurringExpense);
    }

    /**
     * Advance the next transaction date or delete if recurrences are exhausted.
     */
    private function advanceOrDelete(RecurringIncome|RecurringExpense $recurring): void
    {
        if ($recurring->remaining_recurrences !== null) {
            $recurring->remaining_recurrences--;

            if ($recurring->remaining_recurrences <= 0) {
                $recurring->delete();

                return;
            }
        }

        $recurring->next_transaction_at = $recurring->frequency->addToDate(
            $recurring->next_transaction_at
        );
        $recurring->save();
    }
}
