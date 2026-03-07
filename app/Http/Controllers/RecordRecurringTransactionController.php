<?php

namespace App\Http\Controllers;

use App\Http\Requests\RecordRecurringTransactionRequest;
use App\Models\RecurringExpense;
use App\Models\RecurringIncome;
use App\Services\RecurringTransactionService;
use Illuminate\Http\RedirectResponse;

class RecordRecurringTransactionController extends Controller
{
    public function __construct(
        private RecurringTransactionService $recurringTransactionService,
    ) {}

    /**
     * Record an income from a recurring income item.
     */
    public function recordIncome(RecordRecurringTransactionRequest $request, RecurringIncome $recurringIncome): RedirectResponse
    {
        $this->recurringTransactionService->recordIncome($recurringIncome, $request->validated());

        return redirect()->route('recurring-transactions.dashboard')
            ->with('success', 'Income recorded successfully.');
    }

    /**
     * Record an expense from a recurring expense item.
     */
    public function recordExpense(RecordRecurringTransactionRequest $request, RecurringExpense $recurringExpense): RedirectResponse
    {
        $this->recurringTransactionService->recordExpense($recurringExpense, $request->validated());

        return redirect()->route('recurring-transactions.dashboard')
            ->with('success', 'Expense recorded successfully.');
    }
}
