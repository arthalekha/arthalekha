<?php

use App\Enums\Frequency;
use App\Models\Account;
use App\Models\RecurringExpense;
use App\Models\RecurringIncome;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('dashboard displays pending recurring incomes without account', function () {
    $recurringIncome = RecurringIncome::factory()
        ->forUser($this->user)
        ->withoutAccount()
        ->create([
            'next_transaction_at' => now()->subDay(),
            'frequency' => Frequency::Monthly,
        ]);

    $response = $this->get(route('recurring-transactions.dashboard'));

    $response->assertSuccessful();
    $response->assertSee($recurringIncome->description);
});

test('dashboard displays pending recurring expenses without account', function () {
    $recurringExpense = RecurringExpense::factory()
        ->forUser($this->user)
        ->withoutAccount()
        ->create([
            'next_transaction_at' => now()->subDay(),
            'frequency' => Frequency::Monthly,
        ]);

    $response = $this->get(route('recurring-transactions.dashboard'));

    $response->assertSuccessful();
    $response->assertSee($recurringExpense->description);
});

test('dashboard does not display items with an account', function () {
    $account = Account::factory()->forUser($this->user)->create();

    $recurringIncome = RecurringIncome::factory()
        ->forUser($this->user)
        ->forAccount($account)
        ->create([
            'next_transaction_at' => now()->subDay(),
            'frequency' => Frequency::Monthly,
        ]);

    $response = $this->get(route('recurring-transactions.dashboard'));

    $response->assertSuccessful();
    $response->assertDontSee($recurringIncome->description);
});

test('dashboard does not display items with future next_transaction_at', function () {
    $recurringIncome = RecurringIncome::factory()
        ->forUser($this->user)
        ->withoutAccount()
        ->create([
            'next_transaction_at' => now()->addDay(),
            'frequency' => Frequency::Monthly,
        ]);

    $response = $this->get(route('recurring-transactions.dashboard'));

    $response->assertSuccessful();
    $response->assertDontSee($recurringIncome->description);
});

test('dashboard shows empty state when no pending items', function () {
    $response = $this->get(route('recurring-transactions.dashboard'));

    $response->assertSuccessful();
    $response->assertSee('No pending recurring transactions.');
});

test('dashboard requires authentication', function () {
    auth()->logout();

    $response = $this->get(route('recurring-transactions.dashboard'));

    $response->assertRedirect(route('login'));
});
