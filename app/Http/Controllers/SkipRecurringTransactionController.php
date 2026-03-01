<?php

namespace App\Http\Controllers;

use App\Models\RecurringExpense;
use App\Models\RecurringIncome;
use Illuminate\Http\RedirectResponse;

class SkipRecurringTransactionController extends Controller
{
    /**
     * Skip a recurring income occurrence.
     */
    public function skipIncome(RecurringIncome $recurringIncome): RedirectResponse
    {
        $this->advanceOrDelete($recurringIncome);

        return redirect()->route('recurring-transactions.dashboard')
            ->with('success', 'Recurring income skipped.');
    }

    /**
     * Skip a recurring expense occurrence.
     */
    public function skipExpense(RecurringExpense $recurringExpense): RedirectResponse
    {
        $this->advanceOrDelete($recurringExpense);

        return redirect()->route('recurring-transactions.dashboard')
            ->with('success', 'Recurring expense skipped.');
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
