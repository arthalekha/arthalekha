<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Services\AccountReconcileService;
use Illuminate\Console\Command;

class ReconcileAccountsCurrentBalanceCommand extends Command
{
    protected $signature = 'reconcile:accounts:current-balance';

    protected $description = 'Reconciles the current balance of each account';

    public function handle(AccountReconcileService $accountReconcileService): void
    {
        Account::get()
            ->each(fn (Account $account) => $accountReconcileService->reconcile($account));
    }
}
