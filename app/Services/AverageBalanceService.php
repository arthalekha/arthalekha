<?php

namespace App\Services;

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
        $startDate = Date::today()->startOfMonth();

        $endDate = Date::today();

        $this->incomes = Income::query()
            ->selectRaw('DATE(transacted_at) as day, SUM(amount) as total_amount')
            ->where('account_id', $account->id)
            ->whereBetween('transacted_at', [$startDate, $endDate])
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total_amount', 'day');

        $this->expenses = Expense::query()
            ->selectRaw('DATE(transacted_at) as day, SUM(amount) as total_amount')
            ->where('account_id', $account->id)
            ->whereBetween('transacted_at', [$startDate, $endDate])
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total_amount', 'day');

        $this->creditTransfers = Transfer::query()
            ->selectRaw('DATE(transacted_at) as day, SUM(amount) as total_amount')
            ->where('creditor_id', $account->id)
            ->whereBetween('transacted_at', [$startDate, $endDate])
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total_amount', 'day');

        $this->debitTransfers = Transfer::query()
            ->selectRaw('DATE(transacted_at) as day, SUM(amount) as total_amount')
            ->where('debtor_id', $account->id)
            ->whereBetween('transacted_at', [$startDate, $endDate])
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total_amount', 'day');

        $this->data = collect();

        $previousAmount = $account->previousMonthBalance()->value('balance') ?? 0;

        for ($d = $startDate->toMutable(); $d->lte($endDate); $d->addDay()) {

            $dayAmount = $previousAmount
                + $this->incomes->get($d->toDateString(), 0)
                - $this->expenses->get($d->toDateString(), 0)
                + $this->creditTransfers->get($d->toDateString(), 0)
                - $this->debitTransfers->get($d->toDateString(), 0);

            $this->data->push($dayAmount);
        }

        return $this->data->average();
    }
}
