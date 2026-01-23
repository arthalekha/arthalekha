<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Expense;
use App\Models\Income;
use App\Models\RecurringExpense;
use App\Models\RecurringIncome;
use App\Models\RecurringTransfer;
use App\Models\Transfer;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class AccountProjectedBalanceService
{
    /**
     * @var array<string, array{income: float, expense: float, transfer_in: float, transfer_out: float, balance: float}>
     */
    protected array $dailyProjections = [];

    public function __construct(
        protected BalanceService $balanceService
    ) {}

    /**
     * Calculate projected balance for an account within a date range.
     *
     * @return array{
     *     dailyProjections: array<string, array{income: float, expense: float, transfer_in: float, transfer_out: float, balance: float}>,
     *     dates: array<string>,
     *     incomeData: array<float>,
     *     expenseData: array<float>,
     *     transferInData: array<float>,
     *     transferOutData: array<float>,
     *     balanceData: array<float>,
     *     averageBalanceData: array<float>,
     *     summary: array{totalIncome: float, totalExpense: float, totalTransferIn: float, totalTransferOut: float, startingBalance: float, endingBalance: float, averageBalance: float}
     * }
     */
    public function calculate(Account $account, Carbon $startDate, Carbon $endDate): array
    {
        $this->initializeDailyProjections($startDate, $endDate);

        $this->addActualIncomes($account, $startDate, $endDate);
        $this->addActualExpenses($account, $startDate, $endDate);
        $this->addActualCreditTransfers($account, $startDate, $endDate);
        $this->addActualDebitTransfers($account, $startDate, $endDate);

        $this->addProjectedRecurringIncomes($account, $startDate, $endDate);
        $this->addProjectedRecurringExpenses($account, $startDate, $endDate);
        $this->addProjectedRecurringTransfers($account, $startDate, $endDate);

        $startingBalance = $this->getStartingBalance($account, $startDate);
        $this->calculateDailyBalances($startingBalance);

        return $this->formatOutput($startingBalance);
    }

    protected function getStartingBalance(Account $account, Carbon $startDate): float
    {
        $dayBeforeStart = $startDate->copy()->subDay();

        return $this->balanceService->getBalanceForDate($account, $dayBeforeStart);
    }

    protected function initializeDailyProjections(Carbon $startDate, Carbon $endDate): void
    {
        $this->dailyProjections = [];

        $current = $startDate->copy();
        while ($current <= $endDate) {
            $this->dailyProjections[$current->toDateString()] = [
                'income' => 0.0,
                'expense' => 0.0,
                'transfer_in' => 0.0,
                'transfer_out' => 0.0,
                'balance' => 0.0,
            ];
            $current->addDay();
        }
    }

    protected function addActualIncomes(Account $account, Carbon $startDate, Carbon $endDate): void
    {
        $incomes = Income::query()
            ->selectRaw('DATE(transacted_at) as day, SUM(amount) as total_amount')
            ->where('account_id', $account->id)
            ->whereBetween('transacted_at', [$startDate, $endDate])
            ->groupBy('day')
            ->pluck('total_amount', 'day');

        $this->mergeTransactions($incomes, 'income');
    }

    protected function addActualExpenses(Account $account, Carbon $startDate, Carbon $endDate): void
    {
        $expenses = Expense::query()
            ->selectRaw('DATE(transacted_at) as day, SUM(amount) as total_amount')
            ->where('account_id', $account->id)
            ->whereBetween('transacted_at', [$startDate, $endDate])
            ->groupBy('day')
            ->pluck('total_amount', 'day');

        $this->mergeTransactions($expenses, 'expense');
    }

    protected function addActualCreditTransfers(Account $account, Carbon $startDate, Carbon $endDate): void
    {
        $transfers = Transfer::query()
            ->selectRaw('DATE(transacted_at) as day, SUM(amount) as total_amount')
            ->where('creditor_id', $account->id)
            ->whereBetween('transacted_at', [$startDate, $endDate])
            ->groupBy('day')
            ->pluck('total_amount', 'day');

        $this->mergeTransactions($transfers, 'transfer_in');
    }

    protected function addActualDebitTransfers(Account $account, Carbon $startDate, Carbon $endDate): void
    {
        $transfers = Transfer::query()
            ->selectRaw('DATE(transacted_at) as day, SUM(amount) as total_amount')
            ->where('debtor_id', $account->id)
            ->whereBetween('transacted_at', [$startDate, $endDate])
            ->groupBy('day')
            ->pluck('total_amount', 'day');

        $this->mergeTransactions($transfers, 'transfer_out');
    }

    protected function addProjectedRecurringIncomes(Account $account, Carbon $startDate, Carbon $endDate): void
    {
        $recurringIncomes = RecurringIncome::query()
            ->where('account_id', $account->id)
            ->get();

        foreach ($recurringIncomes as $income) {
            $this->projectRecurringTransaction(
                $income->next_transaction_at,
                $income->frequency,
                $income->remaining_recurrences,
                (float) $income->amount,
                $startDate,
                $endDate,
                'income'
            );
        }
    }

    protected function addProjectedRecurringExpenses(Account $account, Carbon $startDate, Carbon $endDate): void
    {
        $recurringExpenses = RecurringExpense::query()
            ->where('account_id', $account->id)
            ->get();

        foreach ($recurringExpenses as $expense) {
            $this->projectRecurringTransaction(
                $expense->next_transaction_at,
                $expense->frequency,
                $expense->remaining_recurrences,
                (float) $expense->amount,
                $startDate,
                $endDate,
                'expense'
            );
        }
    }

    protected function addProjectedRecurringTransfers(Account $account, Carbon $startDate, Carbon $endDate): void
    {
        $creditTransfers = RecurringTransfer::query()
            ->where('creditor_id', $account->id)
            ->get();

        foreach ($creditTransfers as $transfer) {
            $this->projectRecurringTransaction(
                $transfer->next_transaction_at,
                $transfer->frequency,
                $transfer->remaining_recurrences,
                (float) $transfer->amount,
                $startDate,
                $endDate,
                'transfer_in'
            );
        }

        $debitTransfers = RecurringTransfer::query()
            ->where('debtor_id', $account->id)
            ->get();

        foreach ($debitTransfers as $transfer) {
            $this->projectRecurringTransaction(
                $transfer->next_transaction_at,
                $transfer->frequency,
                $transfer->remaining_recurrences,
                (float) $transfer->amount,
                $startDate,
                $endDate,
                'transfer_out'
            );
        }
    }

    protected function projectRecurringTransaction(
        CarbonInterface $nextTransactionAt,
        $frequency,
        ?int $remainingRecurrences,
        float $amount,
        CarbonInterface $startDate,
        CarbonInterface $endDate,
        string $type
    ): void {
        $transactionDate = $nextTransactionAt->copy();
        $count = 0;

        while ($transactionDate <= $endDate) {
            if ($remainingRecurrences !== null && $count >= $remainingRecurrences) {
                break;
            }

            if ($transactionDate >= $startDate) {
                $dateKey = $transactionDate->toDateString();
                if (isset($this->dailyProjections[$dateKey])) {
                    $this->dailyProjections[$dateKey][$type] += $amount;
                }
            }

            $transactionDate = $frequency->addToDate($transactionDate);
            $count++;
        }
    }

    /**
     * @param  Collection<string, float>  $transactions
     */
    protected function mergeTransactions(Collection $transactions, string $type): void
    {
        foreach ($transactions as $date => $amount) {
            if (isset($this->dailyProjections[$date])) {
                $this->dailyProjections[$date][$type] += (float) $amount;
            }
        }
    }

    protected function calculateDailyBalances(float $startingBalance): void
    {
        $runningBalance = $startingBalance;

        foreach ($this->dailyProjections as $date => $data) {
            $runningBalance += $data['income'] - $data['expense'] + $data['transfer_in'] - $data['transfer_out'];
            $this->dailyProjections[$date]['balance'] = $runningBalance;
        }
    }

    /**
     * @return array{
     *     dailyProjections: array<string, array{income: float, expense: float, transfer_in: float, transfer_out: float, balance: float}>,
     *     dates: array<string>,
     *     incomeData: array<float>,
     *     expenseData: array<float>,
     *     transferInData: array<float>,
     *     transferOutData: array<float>,
     *     balanceData: array<float>,
     *     averageBalanceData: array<float>,
     *     summary: array{totalIncome: float, totalExpense: float, totalTransferIn: float, totalTransferOut: float, startingBalance: float, endingBalance: float, averageBalance: float}
     * }
     */
    protected function formatOutput(float $startingBalance): array
    {
        $dates = [];
        $incomeData = [];
        $expenseData = [];
        $transferInData = [];
        $transferOutData = [];
        $balanceData = [];
        $totalIncome = 0.0;
        $totalExpense = 0.0;
        $totalTransferIn = 0.0;
        $totalTransferOut = 0.0;

        foreach ($this->dailyProjections as $date => $data) {
            $dates[] = Carbon::parse($date)->format('M d');
            $incomeData[] = $data['income'];
            $expenseData[] = $data['expense'];
            $transferInData[] = $data['transfer_in'];
            $transferOutData[] = $data['transfer_out'];
            $balanceData[] = $data['balance'];
            $totalIncome += $data['income'];
            $totalExpense += $data['expense'];
            $totalTransferIn += $data['transfer_in'];
            $totalTransferOut += $data['transfer_out'];
        }

        $endingBalance = ! empty($balanceData) ? end($balanceData) : $startingBalance;
        $averageBalance = ! empty($balanceData) ? array_sum($balanceData) / count($balanceData) : $startingBalance;
        $averageBalanceData = array_fill(0, count($balanceData), $averageBalance);

        return [
            'dailyProjections' => $this->dailyProjections,
            'dates' => $dates,
            'incomeData' => $incomeData,
            'expenseData' => $expenseData,
            'transferInData' => $transferInData,
            'transferOutData' => $transferOutData,
            'balanceData' => $balanceData,
            'averageBalanceData' => $averageBalanceData,
            'summary' => [
                'totalIncome' => $totalIncome,
                'totalExpense' => $totalExpense,
                'totalTransferIn' => $totalTransferIn,
                'totalTransferOut' => $totalTransferOut,
                'startingBalance' => $startingBalance,
                'endingBalance' => $endingBalance,
                'averageBalance' => $averageBalance,
            ],
        ];
    }
}
