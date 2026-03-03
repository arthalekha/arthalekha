<?php

namespace App\Http\Controllers;

use App\Http\Requests\RecordRecurringTransactionRequest;
use App\Models\Expense;
use App\Models\Income;
use App\Models\RecurringExpense;
use App\Models\RecurringIncome;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class RecordRecurringTransactionController extends Controller
{
    /**
     * Record an income from a recurring income item.
     */
    public function recordIncome(RecordRecurringTransactionRequest $request, RecurringIncome $recurringIncome): RedirectResponse
    {
        DB::transaction(function () use ($request, $recurringIncome) {
            $income = Income::create([
                'user_id' => $recurringIncome->user_id,
                'person_id' => $recurringIncome->person_id,
                'account_id' => $request->validated('account_id'),
                'description' => $recurringIncome->description,
                'amount' => $recurringIncome->amount,
                'transacted_at' => $request->validated('transacted_at'),
            ]);

            $income->tags()->sync($recurringIncome->tags->pluck('id'));

            $this->advanceOrDelete($recurringIncome);
        });

        return redirect()->route('recurring-transactions.dashboard')
            ->with('success', 'Income recorded successfully.');
    }

    /**
     * Record an expense from a recurring expense item.
     */
    public function recordExpense(RecordRecurringTransactionRequest $request, RecurringExpense $recurringExpense): RedirectResponse
    {
        DB::transaction(function () use ($request, $recurringExpense) {
            $expense = Expense::create([
                'user_id' => $recurringExpense->user_id,
                'person_id' => $recurringExpense->person_id,
                'account_id' => $request->validated('account_id'),
                'description' => $recurringExpense->description,
                'amount' => $recurringExpense->amount,
                'transacted_at' => $request->validated('transacted_at'),
            ]);

            $expense->tags()->sync($recurringExpense->tags->pluck('id'));

            $this->advanceOrDelete($recurringExpense);
        });

        return redirect()->route('recurring-transactions.dashboard')
            ->with('success', 'Expense recorded successfully.');
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
