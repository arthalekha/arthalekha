<?php

use App\Models\Account;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('command runs successfully with no accounts', function () {
    $this->artisan('reconcile:accounts:current-balance')
        ->assertSuccessful();
});

test('command reconciles account with only incomes', function () {
    $account = Account::factory()->forUser($this->user)->create([
        'initial_balance' => 1000.00,
        'current_balance' => 0.00,
    ]);

    Income::factory()->forUser($this->user)->forAccount($account)->create([
        'amount' => 500.00,
    ]);

    Income::factory()->forUser($this->user)->forAccount($account)->create([
        'amount' => 300.00,
    ]);

    $this->artisan('reconcile:accounts:current-balance')
        ->assertSuccessful();

    $account->refresh();
    // 1000 + 500 + 300 = 1800
    expect($account->current_balance)->toBe('1800.00');
});

test('command reconciles account with only expenses', function () {
    $account = Account::factory()->forUser($this->user)->create([
        'initial_balance' => 2000.00,
        'current_balance' => 0.00,
    ]);

    Expense::factory()->forUser($this->user)->forAccount($account)->create([
        'amount' => 300.00,
    ]);

    Expense::factory()->forUser($this->user)->forAccount($account)->create([
        'amount' => 200.00,
    ]);

    $this->artisan('reconcile:accounts:current-balance')
        ->assertSuccessful();

    $account->refresh();
    // 2000 - 300 - 200 = 1500
    expect($account->current_balance)->toBe('1500.00');
});

test('command reconciles account with transfer in', function () {
    $account = Account::factory()->forUser($this->user)->create([
        'initial_balance' => 1000.00,
        'current_balance' => 0.00,
    ]);

    $otherAccount = Account::factory()->forUser($this->user)->create();

    Transfer::factory()->forUser($this->user)->create([
        'creditor_id' => $account->id,
        'debtor_id' => $otherAccount->id,
        'amount' => 500.00,
    ]);

    $this->artisan('reconcile:accounts:current-balance')
        ->assertSuccessful();

    $account->refresh();
    // 1000 + 500 = 1500
    expect($account->current_balance)->toBe('1500.00');
});

test('command reconciles account with transfer out', function () {
    $account = Account::factory()->forUser($this->user)->create([
        'initial_balance' => 1000.00,
        'current_balance' => 0.00,
    ]);

    $otherAccount = Account::factory()->forUser($this->user)->create();

    Transfer::factory()->forUser($this->user)->create([
        'creditor_id' => $otherAccount->id,
        'debtor_id' => $account->id,
        'amount' => 300.00,
    ]);

    $this->artisan('reconcile:accounts:current-balance')
        ->assertSuccessful();

    $account->refresh();
    // 1000 - 300 = 700
    expect($account->current_balance)->toBe('700.00');
});

test('command reconciles account with all transaction types', function () {
    $account = Account::factory()->forUser($this->user)->create([
        'initial_balance' => 1000.00,
        'current_balance' => 0.00,
    ]);

    $otherAccount = Account::factory()->forUser($this->user)->create([
        'initial_balance' => 5000.00,
        'current_balance' => 0.00,
    ]);

    Income::factory()->forUser($this->user)->forAccount($account)->create([
        'amount' => 500.00,
    ]);

    Expense::factory()->forUser($this->user)->forAccount($account)->create([
        'amount' => 200.00,
    ]);

    Transfer::factory()->forUser($this->user)->create([
        'creditor_id' => $account->id,
        'debtor_id' => $otherAccount->id,
        'amount' => 300.00,
    ]);

    Transfer::factory()->forUser($this->user)->create([
        'creditor_id' => $otherAccount->id,
        'debtor_id' => $account->id,
        'amount' => 100.00,
    ]);

    $this->artisan('reconcile:accounts:current-balance')
        ->assertSuccessful();

    $account->refresh();
    // 1000 + 500 - 200 + 300 - 100 = 1500
    expect($account->current_balance)->toBe('1500.00');
});

test('command reconciles multiple accounts', function () {
    $account1 = Account::factory()->forUser($this->user)->create([
        'initial_balance' => 1000.00,
        'current_balance' => 0.00,
    ]);

    $account2 = Account::factory()->forUser($this->user)->create([
        'initial_balance' => 2000.00,
        'current_balance' => 0.00,
    ]);

    Income::factory()->forUser($this->user)->forAccount($account1)->create([
        'amount' => 500.00,
    ]);

    Expense::factory()->forUser($this->user)->forAccount($account2)->create([
        'amount' => 300.00,
    ]);

    $this->artisan('reconcile:accounts:current-balance')
        ->assertSuccessful();

    $account1->refresh();
    $account2->refresh();

    expect($account1->current_balance)->toBe('1500.00');
    expect($account2->current_balance)->toBe('1700.00');
});

test('command handles negative balance', function () {
    $account = Account::factory()->forUser($this->user)->create([
        'initial_balance' => 100.00,
        'current_balance' => 0.00,
    ]);

    Expense::factory()->forUser($this->user)->forAccount($account)->create([
        'amount' => 500.00,
    ]);

    $this->artisan('reconcile:accounts:current-balance')
        ->assertSuccessful();

    $account->refresh();
    // 100 - 500 = -400
    expect($account->current_balance)->toBe('-400.00');
});

test('command corrects incorrect current balance', function () {
    $account = Account::factory()->forUser($this->user)->create([
        'initial_balance' => 1000.00,
        'current_balance' => 9999.00, // Intentionally wrong
    ]);

    Income::factory()->forUser($this->user)->forAccount($account)->create([
        'amount' => 500.00,
    ]);

    $this->artisan('reconcile:accounts:current-balance')
        ->assertSuccessful();

    $account->refresh();
    // Should be corrected to: 1000 + 500 = 1500
    expect($account->current_balance)->toBe('1500.00');
});

test('command reconciles account with no transactions', function () {
    $account = Account::factory()->forUser($this->user)->create([
        'initial_balance' => 5000.00,
        'current_balance' => 0.00,
    ]);

    $this->artisan('reconcile:accounts:current-balance')
        ->assertSuccessful();

    $account->refresh();
    // Should equal initial balance
    expect($account->current_balance)->toBe('5000.00');
});
