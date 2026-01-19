<?php

use App\Jobs\RecordMonthlyBalancesJob;
use App\Jobs\TransactRecurringExpenseJob;
use App\Jobs\TransactRecurringIncomeJob;
use App\Jobs\TransactRecurringTransferJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new TransactRecurringIncomeJob)->daily();
Schedule::job(new TransactRecurringExpenseJob)->daily();
Schedule::job(new TransactRecurringTransferJob)->daily();
Schedule::job(new RecordMonthlyBalancesJob)->monthlyOn(1, '00:00');
