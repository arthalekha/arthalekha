<?php

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Services\AccountService;
use App\Services\BalanceService;
use Illuminate\Support\Facades\Auth;

class FamilyAccountController extends Controller
{
    public function __construct(
        public AccountService $accountService,
        public BalanceService $balanceService,
    ) {}

    public function __invoke()
    {
        $accounts = $this->accountService->getAccountsForUser(Auth::user());
        $accountTypes = AccountType::cases();

        return view('accounts.index', compact('accounts', 'accountTypes'));
    }
}
