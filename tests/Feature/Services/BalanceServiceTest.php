<?php

use App\Models\Account;
use App\Models\Balance;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Transfer;
use App\Models\User;
use App\Services\BalanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->service = app(BalanceService::class);
});

test('getMonthlyIncome returns sum of incomes for a month', function () {
    $account = Account::factory()->forUser($this->user)->create();

    Income::factory()->forUser($this->user)->forAccount($account)->create([
        'amount' => 500.00,
        'transacted_at' => '2024-01-15',
    ]);

    Income::factory()->forUser($this->user)->forAccount($account)->create([
        'amount' => 300.00,
        'transacted_at' => '2024-01-20',
    ]);

    Income::factory()->forUser($this->user)->forAccount($account)->create([
        'amount' => 1000.00,
        'transacted_at' => '2024-02-15',
    ]);

    $january = Carbon::parse('2024-01-15');
    $income = $this->service->getMonthlyIncome($account, $january);

    expect($income)->toBe(800.0);
});

test('getMonthlyExpense returns sum of expenses for a month', function () {
    $account = Account::factory()->forUser($this->user)->create();

    Expense::factory()->forUser($this->user)->forAccount($account)->create([
        'amount' => 200.00,
        'transacted_at' => '2024-01-10',
    ]);

    Expense::factory()->forUser($this->user)->forAccount($account)->create([
        'amount' => 150.00,
        'transacted_at' => '2024-01-25',
    ]);

    $january = Carbon::parse('2024-01-15');
    $expense = $this->service->getMonthlyExpense($account, $january);

    expect($expense)->toBe(350.0);
});

test('getMonthlyTransferIn returns sum of transfers into account', function () {
    $account = Account::factory()->forUser($this->user)->create();
    $otherAccount = Account::factory()->forUser($this->user)->create();

    Transfer::factory()->forUser($this->user)->create([
        'creditor_id' => $account->id,
        'debtor_id' => $otherAccount->id,
        'amount' => 500.00,
        'transacted_at' => '2024-01-15',
    ]);

    $january = Carbon::parse('2024-01-15');
    $transferIn = $this->service->getMonthlyTransferIn($account, $january);

    expect($transferIn)->toBe(500.0);
});

test('getMonthlyTransferOut returns sum of transfers out of account', function () {
    $account = Account::factory()->forUser($this->user)->create();
    $otherAccount = Account::factory()->forUser($this->user)->create();

    Transfer::factory()->forUser($this->user)->create([
        'creditor_id' => $otherAccount->id,
        'debtor_id' => $account->id,
        'amount' => 300.00,
        'transacted_at' => '2024-01-15',
    ]);

    $january = Carbon::parse('2024-01-15');
    $transferOut = $this->service->getMonthlyTransferOut($account, $january);

    expect($transferOut)->toBe(300.0);
});

test('calculateBalanceForMonth returns net change for a month', function () {
    $account = Account::factory()->forUser($this->user)->create();
    $otherAccount = Account::factory()->forUser($this->user)->create();

    Income::factory()->forUser($this->user)->forAccount($account)->create([
        'amount' => 1000.00,
        'transacted_at' => '2024-01-10',
    ]);

    Expense::factory()->forUser($this->user)->forAccount($account)->create([
        'amount' => 300.00,
        'transacted_at' => '2024-01-15',
    ]);

    Transfer::factory()->forUser($this->user)->create([
        'creditor_id' => $account->id,
        'debtor_id' => $otherAccount->id,
        'amount' => 200.00,
        'transacted_at' => '2024-01-20',
    ]);

    Transfer::factory()->forUser($this->user)->create([
        'creditor_id' => $otherAccount->id,
        'debtor_id' => $account->id,
        'amount' => 100.00,
        'transacted_at' => '2024-01-25',
    ]);

    $january = Carbon::parse('2024-01-15');
    $change = $this->service->calculateBalanceForMonth($account, $january);

    // 1000 - 300 + 200 - 100 = 800
    expect($change)->toBe(800.0);
});

test('getFirstTransactionDate returns earliest transaction date', function () {
    $account = Account::factory()->forUser($this->user)->create();

    Income::factory()->forUser($this->user)->forAccount($account)->create([
        'transacted_at' => '2024-03-15',
    ]);

    Expense::factory()->forUser($this->user)->forAccount($account)->create([
        'transacted_at' => '2024-01-10',
    ]);

    $firstDate = $this->service->getFirstTransactionDate($account);

    expect($firstDate->format('Y-m-d'))->toBe('2024-01-10');
});

test('getFirstTransactionDate returns null when no transactions', function () {
    $account = Account::factory()->forUser($this->user)->create();

    $firstDate = $this->service->getFirstTransactionDate($account);

    expect($firstDate)->toBeNull();
});

test('saveBalance creates new balance record', function () {
    $account = Account::factory()->forUser($this->user)->create();
    $date = Carbon::parse('2024-01-31');

    $balance = $this->service->saveBalance($account, $date, 1500.00);

    expect($balance)->toBeInstanceOf(Balance::class);
    expect($balance->balance)->toBe('1500.00');
    expect($balance->recorded_until->format('Y-m-d'))->toBe('2024-01-31');
    expect(Balance::count())->toBe(1);
});

test('saveBalance updates existing balance record', function () {
    $account = Account::factory()->forUser($this->user)->create();

    Balance::factory()->forAccount($account)->create([
        'balance' => 1000.00,
        'recorded_until' => '2024-01-31',
    ]);

    $date = Carbon::parse('2024-01-31');
    $balance = $this->service->saveBalance($account, $date, 2000.00);

    expect($balance->balance)->toBe('2000.00');
    expect(Balance::count())->toBe(1);
});

test('backfillBalancesForAccount creates balance records for each month', function () {
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

    $processed = $this->service->backfillBalancesForAccount($account);

    expect($processed)->toBe(3);
    expect(Balance::count())->toBe(3);

    $januaryBalance = Balance::whereDate('recorded_until', '2024-01-31')->first();
    $februaryBalance = Balance::whereDate('recorded_until', '2024-02-29')->first();
    $marchBalance = Balance::whereDate('recorded_until', '2024-03-31')->first();

    expect($januaryBalance->balance)->toBe('1500.00');
    expect($februaryBalance->balance)->toBe('1300.00');
    expect($marchBalance->balance)->toBe('1300.00');
});
