<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Transfer;

class AccountReconcileService
{
    public function reconcile(Account $account)
    {
        $expenses = Expense::query()
            ->where('account_id', $account->id)
            ->sum('amount');

        $incomes = Income::query()
            ->where('account_id', $account->id)
            ->sum('amount');

        $debitTransfers = Transfer::query()
            ->where('debtor_id', $account->id)
            ->sum('amount');

        $creditTransfers = Transfer::query()
            ->where('creditor_id', $account->id)
            ->sum('amount');

        $reconciledBalance =
            $account->initial_balance
            + $incomes
            - $expenses
            + $creditTransfers
            - $debitTransfers;

        $account->update([
            'current_balance' => $reconciledBalance,
        ]);
    }
}
