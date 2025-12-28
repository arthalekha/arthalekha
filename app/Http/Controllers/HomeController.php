<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Income;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(Request $request): View
    {
        $month = $request->input('month', Carbon::now()->format('Y-m'));
        $startDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $endDate = Carbon::createFromFormat('Y-m', $month)->endOfMonth();

        $userId = Auth::id();

        $incomes = Income::query()
            ->where('user_id', $userId)
            ->whereBetween('transacted_at', [$startDate, $endDate])
            ->selectRaw('DATE(transacted_at) as date, SUM(amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date')
            ->toArray();

        $expenses = Expense::query()
            ->where('user_id', $userId)
            ->whereBetween('transacted_at', [$startDate, $endDate])
            ->selectRaw('DATE(transacted_at) as date, SUM(amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date')
            ->toArray();

        $days = [];
        $incomeData = [];
        $expenseData = [];

        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $dateKey = $currentDate->format('Y-m-d');
            $days[] = $currentDate->format('d');
            $incomeData[] = (float) ($incomes[$dateKey] ?? 0);
            $expenseData[] = (float) ($expenses[$dateKey] ?? 0);
            $currentDate->addDay();
        }

        $totalIncome = array_sum($incomeData);
        $totalExpense = array_sum($expenseData);
        $netSavings = $totalIncome - $totalExpense;

        return view('home', [
            'month' => $month,
            'days' => $days,
            'incomeData' => $incomeData,
            'expenseData' => $expenseData,
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'netSavings' => $netSavings,
            'monthName' => $startDate->format('F Y'),
        ]);
    }
}
