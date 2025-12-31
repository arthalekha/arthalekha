<?php

namespace App\Observers;

use App\Models\Account;
use App\Services\AccountService;

class AccountObserver
{
    public function __construct(private AccountService $accountService) {}

    /**
     * Handle the Account "created" event.
     */
    public function created(Account $account): void
    {
        $this->accountService->clearCache($account->user_id);
    }

    /**
     * Handle the Account "updated" event.
     */
    public function updated(Account $account): void
    {
        $this->accountService->clearCache($account->user_id);
    }

    /**
     * Handle the Account "deleted" event.
     */
    public function deleted(Account $account): void
    {
        $this->accountService->clearCache($account->user_id);
    }
}
