<?php

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Services\AccountService;
use App\Services\BalanceService;

class FamilyAccountController extends Controller
{
    public function __construct(
        public AccountService $accountService,
        public BalanceService $balanceService,
    ) {}

    public function __invoke()
    {
        $accounts = $this->accountService->getAccounts();
        $accountTypes = AccountType::cases();

        return view('accounts.index', compact('accounts', 'accountTypes'));
    }
}
