<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Services\BalanceService;
use Illuminate\Console\Command;

class BackfillAccountBalancesCommand extends Command
{
    protected $signature = 'accounts:backfill-balances';

    protected $description = 'Backfill historical balances for all accounts based on transactions';

    public function __construct(protected BalanceService $balanceService)
    {
        parent::__construct();
    }

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
            $processed = $this->balanceService->backfillBalancesForAccount($account);
            $totalProcessed += $processed;
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        $this->info("Backfill complete. Processed {$totalProcessed} balance records.");

        return self::SUCCESS;
    }
}
