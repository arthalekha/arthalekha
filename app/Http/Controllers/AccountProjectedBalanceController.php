<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Services\AccountProjectedBalanceService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountProjectedBalanceController extends Controller
{
    public function __construct(public AccountProjectedBalanceService $service) {}

    public function __invoke(Request $request, Account $account): View
    {
        if ($account->user_id !== Auth::id()) {
            abort(403);
        }

        $filters = [
            'from_date' => $request->input('filter.from_date', Carbon::now()->startOfMonth()->format('Y-m-d')),
            'to_date' => $request->input('filter.to_date', Carbon::now()->endOfMonth()->format('Y-m-d')),
        ];

        $startDate = Carbon::parse($filters['from_date']);
        $endDate = Carbon::parse($filters['to_date']);

        $projectionData = $this->service->calculate($account, $startDate, $endDate);

        return view('accounts.projected-balance', [
            'account' => $account,
            'filters' => $filters,
            'dailyProjections' => $projectionData['dailyProjections'],
            'dates' => $projectionData['dates'],
            'incomeData' => $projectionData['incomeData'],
            'expenseData' => $projectionData['expenseData'],
            'transferInData' => $projectionData['transferInData'],
            'transferOutData' => $projectionData['transferOutData'],
            'balanceData' => $projectionData['balanceData'],
            'averageBalanceData' => $projectionData['averageBalanceData'],
            'summary' => $projectionData['summary'],
        ]);
    }
}
