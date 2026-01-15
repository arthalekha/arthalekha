<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\Balance;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Transfer;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;

class BackfillAccountBalancesCommand extends Command
{
    protected $signature = 'accounts:backfill-balances';

    protected $description = 'Backfill historical balances for all accounts based on transactions';

    public function handle(): int
    {
        $accounts = Account::all();

        if ($accounts->isEmpty()) {
            $this->info('No accounts found.');

            return self::SUCCESS;
        }

        $this->info("Processing {$accounts->count()} accounts...");

        $progressBar = $this->output->createProgressBar($accounts->count());
        $progressBar->start();

        $totalProcessed = 0;

        foreach ($accounts as $account) {
            $processed = $this->processAccount($account);
            $totalProcessed += $processed;
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        $this->info("Backfill complete. Processed {$totalProcessed} balance records.");

        return self::SUCCESS;
    }

    protected function processAccount(Account $account): int
    {
        $firstTransactionDate = $this->getFirstTransactionDate($account);

        if (! $firstTransactionDate) {
            return 0;
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

            $monthlyIncome = $this->getMonthlyIncome($account, $currentMonth);
            $monthlyExpense = $this->getMonthlyExpense($account, $currentMonth);
            $monthlyTransferIn = $this->getMonthlyTransferIn($account, $currentMonth);
            $monthlyTransferOut = $this->getMonthlyTransferOut($account, $currentMonth);

            $runningBalance = $runningBalance + $monthlyIncome - $monthlyExpense + $monthlyTransferIn - $monthlyTransferOut;

            $balance = Balance::where('account_id', $account->id)
                ->whereDate('recorded_until', $monthEnd->toDateString())
                ->first();

            if ($balance) {
                $balance->update(['balance' => $runningBalance]);
            } else {
                Balance::create([
                    'account_id' => $account->id,
                    'balance' => $runningBalance,
                    'recorded_until' => $monthEnd->toDateString(),
                ]);
            }
            $processed++;

            $currentMonth->addMonth();
        }

        return $processed;
    }

    protected function getFirstTransactionDate(Account $account): ?CarbonInterface
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

    protected function getMonthlyIncome(Account $account, CarbonInterface $month): float
    {
        return (float) Income::where('account_id', $account->id)
            ->whereYear('transacted_at', $month->year)
            ->whereMonth('transacted_at', $month->month)
            ->sum('amount');
    }

    protected function getMonthlyExpense(Account $account, CarbonInterface $month): float
    {
        return (float) Expense::where('account_id', $account->id)
            ->whereYear('transacted_at', $month->year)
            ->whereMonth('transacted_at', $month->month)
            ->sum('amount');
    }

    protected function getMonthlyTransferIn(Account $account, CarbonInterface $month): float
    {
        return (float) Transfer::where('creditor_id', $account->id)
            ->whereYear('transacted_at', $month->year)
            ->whereMonth('transacted_at', $month->month)
            ->sum('amount');
    }

    protected function getMonthlyTransferOut(Account $account, CarbonInterface $month): float
    {
        return (float) Transfer::where('debtor_id', $account->id)
            ->whereYear('transacted_at', $month->year)
            ->whereMonth('transacted_at', $month->month)
            ->sum('amount');
    }
}
