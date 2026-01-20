<?php

namespace App\Services;

use App\Enums\Frequency;
use App\Models\Account;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Transfer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;

class AverageBalanceService
{
    protected Collection $data;

    protected Collection $incomes;

    protected Collection $expenses;

    protected Collection $creditTransfers;

    protected Collection $debitTransfers;

    public function calculate(Account $account): float|int|null
    {
        $frequency = $this->getFrequency($account);
        $startDate = $frequency->startOfPeriod(Date::today());

        $endDate = Date::now();

        $this->incomes = $this->fetchIncomes($account, $startDate, $endDate);

        $this->expenses = $this->fetchExpenses($account, $startDate, $endDate);

        $this->creditTransfers = $this->fetchCreditTransfers($account, $startDate, $endDate);

        $this->debitTransfers = $this->fetchDebitTransfers($account, $startDate, $endDate);

        $this->data = collect();

        $dayAmount = $account->previousMonthBalance()->value('balance') ?? $account->initial_balance;

        for ($d = $startDate->toMutable(); $d->lte($endDate); $d->addDay()) {

            $dayAmount = $dayAmount
                + $this->incomes->get($d->toDateString(), 0)
                - $this->expenses->get($d->toDateString(), 0)
                + $this->creditTransfers->get($d->toDateString(), 0)
                - $this->debitTransfers->get($d->toDateString(), 0);

            $this->data->push($dayAmount);
        }

        return $this->data->average();
    }

    private function getFrequency(Account $account): Frequency
    {
        $frequencyValue = $account->data['average_balance_frequency'] ?? null;

        if ($frequencyValue === null) {
            return Frequency::Monthly;
        }

        return Frequency::tryFrom($frequencyValue) ?? Frequency::Monthly;
    }

    private function fetchIncomes(Account $account, mixed $startDate, mixed $endDate): Collection
    {
        return Income::query()
            ->selectRaw('DATE(transacted_at) as day, SUM(amount) as total_amount')
            ->where('account_id', $account->id)
            ->whereBetween('transacted_at', [$startDate, $endDate])
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total_amount', 'day');
    }

    private function fetchExpenses(Account $account, mixed $startDate, mixed $endDate): Collection
    {
        return Expense::query()
            ->selectRaw('DATE(transacted_at) as day, SUM(amount) as total_amount')
            ->where('account_id', $account->id)
            ->whereBetween('transacted_at', [$startDate, $endDate])
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total_amount', 'day');
    }

    private function fetchCreditTransfers(Account $account, mixed $startDate, mixed $endDate): Collection
    {
        return Transfer::query()
            ->selectRaw('DATE(transacted_at) as day, SUM(amount) as total_amount')
            ->where('creditor_id', $account->id)
            ->whereBetween('transacted_at', [$startDate, $endDate])
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total_amount', 'day');
    }

    private function fetchDebitTransfers(Account $account, mixed $startDate, mixed $endDate): Collection
    {
        return Transfer::query()
            ->selectRaw('DATE(transacted_at) as day, SUM(amount) as total_amount')
            ->where('debtor_id', $account->id)
            ->whereBetween('transacted_at', [$startDate, $endDate])
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total_amount', 'day');
    }
}
