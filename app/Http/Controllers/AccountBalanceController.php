<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Contracts\View\View;

class AccountBalanceController extends Controller
{
    /**
     * Display historical balances for an account.
     */
    public function __invoke(Account $account): View
    {
        $balances = $account->balances()
            ->orderByDesc('recorded_until')
            ->paginate(12);

        return view('accounts.balances', [
            'account' => $account,
            'balances' => $balances,
        ]);
    }
}
