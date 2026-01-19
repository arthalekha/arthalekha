<?php

use App\Models\Account;
use App\Models\Balance;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Transfer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('command runs successfully with no accounts', function () {
    $this->artisan('accounts:backfill-balances')
        ->expectsOutput('No accounts found.')
        ->assertSuccessful();
});

test('command creates balance records from income transactions', function () {
    Carbon::setTestNow('2024-03-15');

    $account = Account::factory()->forUser($this->user)->create([
        'initial_balance' => 1000.00,
    ]);

    Income::factory()->forUser($this->user)->forAccount($account)->create([
        'amount' => 500.00,
        'transacted_at' => '2024-01-15',
    ]);

    Income::factory()->forUser($this->user)->forAccount($account)->create([
        'amount' => 300.00,
        'transacted_at' => '2024-02-10',
    ]);

    $this->artisan('accounts:backfill-balances')
        ->assertSuccessful();

    expect(Balance::count())->toBe(2);

    $januaryBalance = Balance::whereDate('recorded_until', '2024-01-31')->first();
    $februaryBalance = Balance::whereDate('recorded_until', '2024-02-29')->first();

    expect($januaryBalance->balance)->toBe('1500.00');
    expect($februaryBalance->balance)->toBe('1800.00');
});

test('command creates balance records from expense transactions', function () {
    Carbon::setTestNow('2024-03-15');

    $account = Account::factory()->forUser($this->user)->create([
        'initial_balance' => 2000.00,
    ]);

    Expense::factory()->forUser($this->user)->forAccount($account)->create([
        'amount' => 300.00,
        'transacted_at' => '2024-01-20',
    ]);

    Expense::factory()->forUser($this->user)->forAccount($account)->create([
        'amount' => 200.00,
        'transacted_at' => '2024-02-15',
    ]);

    $this->artisan('accounts:backfill-balances')
        ->assertSuccessful();

    expect(Balance::count())->toBe(2);

    $januaryBalance = Balance::whereDate('recorded_until', '2024-01-31')->first();
    $februaryBalance = Balance::whereDate('recorded_until', '2024-02-29')->first();

    expect($januaryBalance->balance)->toBe('1700.00');
    expect($februaryBalance->balance)->toBe('1500.00');
});

test('command handles transfer in transactions', function () {
    Carbon::setTestNow('2024-03-15');

    $account = Account::factory()->forUser($this->user)->create([
        'initial_balance' => 1000.00,
    ]);

    $otherAccount = Account::factory()->forUser($this->user)->create();

    Transfer::factory()->forUser($this->user)->create([
        'creditor_id' => $account->id,
        'debtor_id' => $otherAccount->id,
        'amount' => 500.00,
        'transacted_at' => '2024-01-15',
    ]);

    $this->artisan('accounts:backfill-balances')
        ->assertSuccessful();

    $balance = Balance::where('account_id', $account->id)
        ->whereDate('recorded_until', '2024-01-31')
        ->first();

    expect($balance->balance)->toBe('1500.00');
});

test('command handles transfer out transactions', function () {
    Carbon::setTestNow('2024-03-15');

    $account = Account::factory()->forUser($this->user)->create([
        'initial_balance' => 1000.00,
    ]);

    $otherAccount = Account::factory()->forUser($this->user)->create();

    Transfer::factory()->forUser($this->user)->create([
        'creditor_id' => $otherAccount->id,
        'debtor_id' => $account->id,
        'amount' => 300.00,
        'transacted_at' => '2024-01-15',
    ]);

    $this->artisan('accounts:backfill-balances')
        ->assertSuccessful();

    $balance = Balance::where('account_id', $account->id)
        ->whereDate('recorded_until', '2024-01-31')
        ->first();

    expect($balance->balance)->toBe('700.00');
});

test('command combines all transaction types correctly', function () {
    Carbon::setTestNow('2024-03-15');

    $account = Account::factory()->forUser($this->user)->create([
        'initial_balance' => 1000.00,
    ]);

    $otherAccount = Account::factory()->forUser($this->user)->create();

    Income::factory()->forUser($this->user)->forAccount($account)->create([
        'amount' => 500.00,
        'transacted_at' => '2024-01-10',
    ]);

    Expense::factory()->forUser($this->user)->forAccount($account)->create([
        'amount' => 200.00,
        'transacted_at' => '2024-01-15',
    ]);

    Transfer::factory()->forUser($this->user)->create([
        'creditor_id' => $account->id,
        'debtor_id' => $otherAccount->id,
        'amount' => 300.00,
        'transacted_at' => '2024-01-20',
    ]);

    Transfer::factory()->forUser($this->user)->create([
        'creditor_id' => $otherAccount->id,
        'debtor_id' => $account->id,
        'amount' => 100.00,
        'transacted_at' => '2024-01-25',
    ]);

    $this->artisan('accounts:backfill-balances')
        ->assertSuccessful();

    $balance = Balance::where('account_id', $account->id)
        ->whereDate('recorded_until', '2024-01-31')
        ->first();

    // 1000 + 500 - 200 + 300 - 100 = 1500
    expect($balance->balance)->toBe('1500.00');
});

test('command updates existing balance records', function () {
    Carbon::setTestNow('2024-03-15');

    $account = Account::factory()->forUser($this->user)->create([
        'initial_balance' => 1000.00,
    ]);

    Income::factory()->forUser($this->user)->forAccount($account)->create([
        'amount' => 500.00,
        'transacted_at' => '2024-01-15',
    ]);

    Balance::factory()->forAccount($account)->create([
        'balance' => 9999.00,
        'recorded_until' => '2024-01-31',
    ]);

    $this->artisan('accounts:backfill-balances')
        ->assertSuccessful();

    expect(Balance::count())->toBe(2);

    $updatedBalance = Balance::whereDate('recorded_until', '2024-01-31')->first();
    expect($updatedBalance->balance)->toBe('1500.00');
});

test('command calculates running balance across multiple months', function () {
    Carbon::setTestNow('2024-04-15');

    $account = Account::factory()->forUser($this->user)->create([
        'initial_balance' => 1000.00,
    ]);

    Income::factory()->forUser($this->user)->forAccount($account)->create([
        'amount' => 500.00,
        'transacted_at' => '2024-01-15',
    ]);

    Expense::factory()->forUser($this->user)->forAccount($account)->create([
        'amount' => 200.00,
        'transacted_at' => '2024-02-15',
    ]);

    Income::factory()->forUser($this->user)->forAccount($account)->create([
        'amount' => 300.00,
        'transacted_at' => '2024-03-15',
    ]);

    $this->artisan('accounts:backfill-balances')
        ->assertSuccessful();

    expect(Balance::count())->toBe(3);

    $januaryBalance = Balance::whereDate('recorded_until', '2024-01-31')->first();
    $februaryBalance = Balance::whereDate('recorded_until', '2024-02-29')->first();
    $marchBalance = Balance::whereDate('recorded_until', '2024-03-31')->first();

    expect($januaryBalance->balance)->toBe('1500.00');
    expect($februaryBalance->balance)->toBe('1300.00');
    expect($marchBalance->balance)->toBe('1600.00');
});

test('command does not create balance for current month', function () {
    Carbon::setTestNow('2024-02-15');

    $account = Account::factory()->forUser($this->user)->create([
        'initial_balance' => 1000.00,
    ]);

    Income::factory()->forUser($this->user)->forAccount($account)->create([
        'amount' => 500.00,
        'transacted_at' => '2024-02-10',
    ]);

    $this->artisan('accounts:backfill-balances')
        ->assertSuccessful();

    expect(Balance::count())->toBe(0);
});

test('command creates balance for previous month only', function () {
    Carbon::setTestNow('2024-02-15');

    $account = Account::factory()->forUser($this->user)->create([
        'initial_balance' => 1000.00,
    ]);

    Income::factory()->forUser($this->user)->forAccount($account)->create([
        'amount' => 500.00,
        'transacted_at' => '2024-01-10',
    ]);

    $this->artisan('accounts:backfill-balances')
        ->assertSuccessful();

    expect(Balance::count())->toBe(1);

    $balance = Balance::first();
    expect($balance->recorded_until->format('Y-m-d'))->toBe('2024-01-31');
});

test('command processes multiple accounts', function () {
    Carbon::setTestNow('2024-03-15');

    $account1 = Account::factory()->forUser($this->user)->create([
        'initial_balance' => 1000.00,
    ]);

    $account2 = Account::factory()->forUser($this->user)->create([
        'initial_balance' => 2000.00,
    ]);

    Income::factory()->forUser($this->user)->forAccount($account1)->create([
        'amount' => 500.00,
        'transacted_at' => '2024-01-15',
    ]);

    Expense::factory()->forUser($this->user)->forAccount($account2)->create([
        'amount' => 300.00,
        'transacted_at' => '2024-02-15',
    ]);

    $this->artisan('accounts:backfill-balances')
        ->assertSuccessful();

    $account1Balances = Balance::where('account_id', $account1->id)->count();
    $account2Balances = Balance::where('account_id', $account2->id)->count();

    expect($account1Balances)->toBe(2);
    expect($account2Balances)->toBe(1);
});

test('command handles negative balances', function () {
    Carbon::setTestNow('2024-03-15');

    $account = Account::factory()->forUser($this->user)->create([
        'initial_balance' => 100.00,
    ]);

    Expense::factory()->forUser($this->user)->forAccount($account)->create([
        'amount' => 500.00,
        'transacted_at' => '2024-01-15',
    ]);

    $this->artisan('accounts:backfill-balances')
        ->assertSuccessful();

    $balance = Balance::first();
    expect($balance->balance)->toBe('-400.00');
});

test('command uses initial balance as starting point', function () {
    Carbon::setTestNow('2024-03-15');

    $account = Account::factory()->forUser($this->user)->create([
        'initial_balance' => 5000.00,
    ]);

    Income::factory()->forUser($this->user)->forAccount($account)->create([
        'amount' => 100.00,
        'transacted_at' => '2024-01-15',
    ]);

    $this->artisan('accounts:backfill-balances')
        ->assertSuccessful();

    $balance = Balance::first();
    expect($balance->balance)->toBe('5100.00');
});
