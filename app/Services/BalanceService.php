<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Balance;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Transfer;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class BalanceService
{
    public function createInitialBalanceEntries(Account $account): void
    {
        $initialDate = $account->initial_date->toMutable();

        $now = Carbon::now();

        $data = [];

        while ($initialDate->lt(Carbon::today()->startOfMonth())) {
            $initialDate->endOfMonth();

            $data[] = [
                'account_id' => $account->id,
                'recorded_until' => $initialDate->toDateString(),
                'balance' => $account->initial_balance,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $initialDate->addDay();
        }

        if (! empty($data)) {
            Balance::query()->insert($data);
        }
    }

    /**
     * Calculate the balance for an account at the end of a specific month.
     */
    public function calculateBalanceForMonth(Account $account, CarbonInterface $month): float
    {
        $startDate = $month->startOfMonth();
        $endDate = $month->endOfMonth();

        return $this->calculateBalanceForPeriod($account, $startDate, $endDate);
    }

    /**
     * Backfill balance records for an account from the first transaction to the previous month.
     *
     * @return int Number of balance records processed
     */
    public function backfillBalancesForAccount(Account $account): int
    {
        $firstTransactionDate = $this->getFirstTransactionDate($account);

        if (! $firstTransactionDate) {
            $firstTransactionDate = $account->initial_date;
        }

        $startMonth = Carbon::parse($firstTransactionDate)->startOfMonth();
        $endMonth = Carbon::now()->subMonth()->endOfMonth();

        if ($startMonth->greaterThan($endMonth)) {
            return 0;
        }

        $processed = 0;
        $runningBalance = (float) $account->initial_balance;
        $currentMonth = $startMonth->copy();

        while ($currentMonth->lte($endMonth)) {
            $monthEnd = $currentMonth->copy()->endOfMonth();

            $monthlyChange = $this->calculateBalanceForMonth($account, $currentMonth);
            $runningBalance += $monthlyChange;

            $this->saveBalance($account, $monthEnd, $runningBalance);
            $processed++;

            $currentMonth->addMonth();
        }

        return $processed;
    }

    /**
     * Save or update a balance record for an account.
     */
    public function saveBalance(Account $account, CarbonInterface $date, float $balance): Balance
    {
        $existingBalance = Balance::where('account_id', $account->id)
            ->whereDate('recorded_until', $date->toDateString())
            ->first();

        if ($existingBalance) {
            $existingBalance->update(['balance' => $balance]);

            return $existingBalance;
        }

        return Balance::create([
            'account_id' => $account->id,
            'balance' => $balance,
            'recorded_until' => $date->toDateString(),
        ]);
    }

    /**
     * Get the first transaction date for an account.
     */
    public function getFirstTransactionDate(Account $account): ?CarbonInterface
    {
        $dates = collect();

        $firstIncome = Income::where('account_id', $account->id)
            ->orderBy('transacted_at')
            ->first();
        if ($firstIncome) {
            $dates->push($firstIncome->transacted_at);
        }

        $firstExpense = Expense::where('account_id', $account->id)
            ->orderBy('transacted_at')
            ->first();
        if ($firstExpense) {
            $dates->push($firstExpense->transacted_at);
        }

        $firstTransferIn = Transfer::where('creditor_id', $account->id)
            ->orderBy('transacted_at')
            ->first();
        if ($firstTransferIn) {
            $dates->push($firstTransferIn->transacted_at);
        }

        $firstTransferOut = Transfer::where('debtor_id', $account->id)
            ->orderBy('transacted_at')
            ->first();
        if ($firstTransferOut) {
            $dates->push($firstTransferOut->transacted_at);
        }

        return $dates->min();
    }

    /**
     * Get total income for an account in a specific month.
     */
    public function getPeriodicIncome(Account $account, CarbonInterface $startDate, CarbonInterface $endDate): float
    {
        return (float) Income::where('account_id', $account->id)
            ->whereDate('transacted_at', '>=', $startDate->toDateString())
            ->whereDate('transacted_at', '<=', $endDate->toDateString())
            ->sum('amount');
    }

    /**
     * Get total expenses for an account in a specific month.
     */
    public function getPeriodicExpense(Account $account, CarbonInterface $startDate, CarbonInterface $endDate): float
    {
        return (float) Expense::where('account_id', $account->id)
            ->whereDate('transacted_at', '>=', $startDate->toDateString())
            ->whereDate('transacted_at', '<=', $endDate->toDateString())
            ->sum('amount');
    }

    /**
     * Get total transfers into an account in a specific month.
     */
    public function getPeriodicTransferIn(Account $account, CarbonInterface $startDate, CarbonInterface $endDate): float
    {
        return (float) Transfer::where('creditor_id', $account->id)
            ->whereDate('transacted_at', '>=', $startDate->toDateString())
            ->whereDate('transacted_at', '<=', $endDate->toDateString())
            ->sum('amount');
    }

    /**
     * Get total transfers out of an account in a specific month.
     */
    public function getPeriodicTransferOut(Account $account, CarbonInterface $startDate, CarbonInterface $endDate): float
    {
        return (float) Transfer::where('debtor_id', $account->id)
            ->whereDate('transacted_at', '>=', $startDate->toDateString())
            ->whereDate('transacted_at', '<=', $endDate->toDateString())
            ->sum('amount');
    }

    public function incrementBalance(int $accountId, float $amount, CarbonInterface $date): void
    {
        Balance::query()
            ->whereDate('recorded_until', '>=', $date->endOfMonth())
            ->where('account_id', $accountId)
            ->increment('balance', $amount);
    }

    public function decrementBalance(int $accountId, float $amount, CarbonInterface $date): void
    {
        Balance::query()
            ->whereDate('recorded_until', '>=', $date->endOfMonth())
            ->where('account_id', $accountId)
            ->decrement('balance', $amount);
    }

    public function getBalanceForDate(Account $account, CarbonInterface $date): float
    {
        // Check the previous month balance or use initial Balance
        $balance = $account->previousMonthBalance()->first();

        if ($balance) {
        }
    }

    public function calculateBalanceForPeriod(Account $account, CarbonInterface $startDate, CarbonInterface $endDate): float
    {
        $periodicIncome = $this->getPeriodicIncome($account, $startDate, $endDate);
        $periodicExpense = $this->getPeriodicExpense($account, $startDate, $endDate);
        $periodicTransferIn = $this->getPeriodicTransferIn($account, $startDate, $endDate);
        $periodicTransferOut = $this->getPeriodicTransferOut($account, $startDate, $endDate);

        return $periodicIncome - $periodicExpense + $periodicTransferIn - $periodicTransferOut;
    }
}
