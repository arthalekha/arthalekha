<?php

use App\Jobs\RecordMonthlyBalancesJob;
use App\Models\Account;
use App\Models\Balance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('it records balance for all accounts', function () {
    Carbon::setTestNow('2024-02-15');

    $account1 = Account::factory()->forUser($this->user)->create(['current_balance' => 1000.00]);
    $account2 = Account::factory()->forUser($this->user)->create(['current_balance' => 2500.50]);

    (new RecordMonthlyBalancesJob)->handle();

    expect(Balance::count())->toBe(2);

    $balance1 = Balance::where('account_id', $account1->id)->first();
    $balance2 = Balance::where('account_id', $account2->id)->first();

    expect($balance1->balance)->toBe('1000.00')
        ->and($balance1->recorded_until->format('Y-m-d'))->toBe('2024-01-31')
        ->and($balance2->balance)->toBe('2500.50')
        ->and($balance2->recorded_until->format('Y-m-d'))->toBe('2024-01-31');
});

test('it records balance for last day of previous month', function () {
    Carbon::setTestNow('2024-03-10');

    $account = Account::factory()->forUser($this->user)->create(['current_balance' => 5000.00]);

    (new RecordMonthlyBalancesJob)->handle();

    $balance = Balance::first();
    expect($balance->recorded_until->format('Y-m-d'))->toBe('2024-02-29'); // Leap year
});

test('it skips accounts that already have balance recorded for the month', function () {
    Carbon::setTestNow('2024-02-15');

    $account = Account::factory()->forUser($this->user)->create(['current_balance' => 1000.00]);

    // Pre-existing balance for the same month
    Balance::factory()->forAccount($account)->create([
        'balance' => 500.00,
        'recorded_until' => '2024-01-31',
    ]);

    (new RecordMonthlyBalancesJob)->handle();

    expect(Balance::count())->toBe(1);
    expect(Balance::first()->balance)->toBe('500.00'); // Original balance unchanged
});

test('it records balance for accounts without existing balance for the month', function () {
    Carbon::setTestNow('2024-02-15');

    $accountWithBalance = Account::factory()->forUser($this->user)->create(['current_balance' => 1000.00]);
    $accountWithoutBalance = Account::factory()->forUser($this->user)->create(['current_balance' => 2000.00]);

    // Pre-existing balance only for first account
    Balance::factory()->forAccount($accountWithBalance)->create([
        'balance' => 500.00,
        'recorded_until' => '2024-01-31',
    ]);

    (new RecordMonthlyBalancesJob)->handle();

    expect(Balance::count())->toBe(2);

    $newBalance = Balance::where('account_id', $accountWithoutBalance->id)->first();
    expect($newBalance->balance)->toBe('2000.00');
});

test('it handles accounts with negative balances', function () {
    Carbon::setTestNow('2024-02-15');

    $account = Account::factory()->forUser($this->user)->create(['current_balance' => -500.00]);

    (new RecordMonthlyBalancesJob)->handle();

    $balance = Balance::first();
    expect($balance->balance)->toBe('-500.00');
});

test('it handles accounts with zero balance', function () {
    Carbon::setTestNow('2024-02-15');

    $account = Account::factory()->forUser($this->user)->create(['current_balance' => 0.00]);

    (new RecordMonthlyBalancesJob)->handle();

    $balance = Balance::first();
    expect($balance->balance)->toBe('0.00');
});

test('it processes multiple accounts from different users', function () {
    Carbon::setTestNow('2024-02-15');

    $user2 = User::factory()->create();

    Account::factory()->forUser($this->user)->create(['current_balance' => 1000.00]);
    Account::factory()->forUser($user2)->create(['current_balance' => 2000.00]);

    (new RecordMonthlyBalancesJob)->handle();

    expect(Balance::count())->toBe(2);
});

test('it does not create duplicates when run multiple times', function () {
    Carbon::setTestNow('2024-02-15');

    Account::factory()->forUser($this->user)->create(['current_balance' => 1000.00]);

    (new RecordMonthlyBalancesJob)->handle();
    (new RecordMonthlyBalancesJob)->handle();

    expect(Balance::count())->toBe(1);
});

test('it records different balances for different months', function () {
    $account = Account::factory()->forUser($this->user)->create(['current_balance' => 1000.00]);

    Carbon::setTestNow('2024-02-15');
    (new RecordMonthlyBalancesJob)->handle();

    $account->update(['current_balance' => 1500.00]);

    Carbon::setTestNow('2024-03-15');
    (new RecordMonthlyBalancesJob)->handle();

    expect(Balance::count())->toBe(2);

    $januaryBalance = Balance::whereDate('recorded_until', '2024-01-31')->first();
    $februaryBalance = Balance::whereDate('recorded_until', '2024-02-29')->first();

    expect($januaryBalance->balance)->toBe('1000.00')
        ->and($februaryBalance->balance)->toBe('1500.00');
});
