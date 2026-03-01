<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\RecurringExpense;
use App\Models\RecurringIncome;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class RecurringTransactionDashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): View
    {
        $recurringIncomes = RecurringIncome::query()
            ->where('next_transaction_at', '<=', now())
            ->whereNull('account_id')
            ->with(['person', 'tags'])
            ->get();

        $recurringExpenses = RecurringExpense::query()
            ->where('next_transaction_at', '<=', now())
            ->whereNull('account_id')
            ->with(['person', 'tags'])
            ->get();

        $accounts = Account::all();

        return view('recurring-transactions.dashboard', [
            'recurringIncomes' => $recurringIncomes,
            'recurringExpenses' => $recurringExpenses,
            'accounts' => $accounts,
        ]);
    }
}
