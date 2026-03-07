<?php

namespace App\Http\Controllers;

use App\Models\RecurringExpense;
use App\Models\RecurringIncome;
use App\Services\RecurringTransactionService;
use Illuminate\Http\RedirectResponse;

class SkipRecurringTransactionController extends Controller
{
    public function __construct(
        private RecurringTransactionService $recurringTransactionService,
    ) {}

    /**
     * Skip a recurring income occurrence.
     */
    public function skipIncome(RecurringIncome $recurringIncome): RedirectResponse
    {
        $this->recurringTransactionService->skipIncome($recurringIncome);

        return redirect()->route('recurring-transactions.dashboard')
            ->with('success', 'Recurring income skipped.');
    }

    /**
     * Skip a recurring expense occurrence.
     */
    public function skipExpense(RecurringExpense $recurringExpense): RedirectResponse
    {
        $this->recurringTransactionService->skipExpense($recurringExpense);

        return redirect()->route('recurring-transactions.dashboard')
            ->with('success', 'Recurring expense skipped.');
    }
}
