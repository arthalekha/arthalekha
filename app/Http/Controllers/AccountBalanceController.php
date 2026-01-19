<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class AccountBalanceController extends Controller
{
    /**
     * Display historical balances for an account.
     */
    public function __invoke(Account $account): View
    {
        if ($account->user_id !== Auth::id()) {
            abort(403);
        }

        $balances = $account->balances()
            ->orderByDesc('recorded_until')
            ->paginate(12);

        return view('accounts.balances', [
            'account' => $account,
            'balances' => $balances,
        ]);
    }
}
