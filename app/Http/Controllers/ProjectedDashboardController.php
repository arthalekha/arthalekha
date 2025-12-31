<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\RecurringExpense;
use App\Models\RecurringIncome;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProjectedDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $userId = Auth::id();
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->addYear()->endOfMonth();

        $currentBalance = Account::query()
            ->where('user_id', $userId)
            ->sum('current_balance');

        $recurringIncomes = RecurringIncome::query()
            ->where('user_id', $userId)
            ->get();

        $recurringExpenses = RecurringExpense::query()
            ->where('user_id', $userId)
            ->get();

        $monthlyProjections = $this->calculateMonthlyProjections(
            $recurringIncomes,
            $recurringExpenses,
            $startDate,
            $endDate
        );

        $months = array_keys($monthlyProjections);
        $incomeData = array_column($monthlyProjections, 'income');
        $expenseData = array_column($monthlyProjections, 'expense');

        $balanceData = $this->calculateProjectedBalance($monthlyProjections, (float) $currentBalance);

        $totalProjectedIncome = array_sum($incomeData);
        $totalProjectedExpense = array_sum($expenseData);
        $projectedNetSavings = $totalProjectedIncome - $totalProjectedExpense;

        return view('projected-dashboard', [
            'months' => $months,
            'incomeData' => $incomeData,
            'expenseData' => $expenseData,
            'balanceData' => $balanceData,
            'currentBalance' => $currentBalance,
            'totalProjectedIncome' => $totalProjectedIncome,
            'totalProjectedExpense' => $totalProjectedExpense,
            'projectedNetSavings' => $projectedNetSavings,
            'monthlyProjections' => $monthlyProjections,
        ]);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<int, RecurringIncome>  $recurringIncomes
     * @param  \Illuminate\Database\Eloquent\Collection<int, RecurringExpense>  $recurringExpenses
     * @return array<string, array{income: float, expense: float}>
     */
    private function calculateMonthlyProjections(
        $recurringIncomes,
        $recurringExpenses,
        Carbon $startDate,
        Carbon $endDate
    ): array {
        $projections = [];

        $current = $startDate->copy();
        while ($current <= $endDate) {
            $monthKey = $current->format('M Y');
            $projections[$monthKey] = ['income' => 0.0, 'expense' => 0.0];
            $current->addMonth();
        }

        foreach ($recurringIncomes as $income) {
            $this->projectTransactions(
                $income->next_transaction_at,
                $income->frequency,
                $income->remaining_recurrences,
                (float) $income->amount,
                $startDate,
                $endDate,
                $projections,
                'income'
            );
        }

        foreach ($recurringExpenses as $expense) {
            $this->projectTransactions(
                $expense->next_transaction_at,
                $expense->frequency,
                $expense->remaining_recurrences,
                (float) $expense->amount,
                $startDate,
                $endDate,
                $projections,
                'expense'
            );
        }

        return $projections;
    }

    /**
     * @param  array<string, array{income: float, expense: float}>  $projections
     */
    private function projectTransactions(
        CarbonInterface $nextTransactionAt,
        $frequency,
        ?int $remainingRecurrences,
        float $amount,
        CarbonInterface $startDate,
        CarbonInterface $endDate,
        array &$projections,
        string $type
    ): void {
        $transactionDate = $nextTransactionAt->copy();
        $count = 0;

        while ($transactionDate <= $endDate) {
            if ($remainingRecurrences !== null && $count >= $remainingRecurrences) {
                break;
            }

            if ($transactionDate >= $startDate) {
                $monthKey = $transactionDate->format('M Y');
                if (isset($projections[$monthKey])) {
                    $projections[$monthKey][$type] += $amount;
                }
            }

            $transactionDate = $frequency->addToDate($transactionDate);
            $count++;
        }
    }

    /**
     * @param  array<string, array{income: float, expense: float}>  $monthlyProjections
     * @return array<float>
     */
    private function calculateProjectedBalance(array $monthlyProjections, float $startingBalance): array
    {
        $balanceData = [];
        $runningBalance = $startingBalance;

        foreach ($monthlyProjections as $data) {
            $runningBalance += $data['income'] - $data['expense'];
            $balanceData[] = $runningBalance;
        }

        return $balanceData;
    }
}
