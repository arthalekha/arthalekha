<?php

use App\Models\Account;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Person;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->account = Account::factory()->forUser($this->user)->create();
});

test('guest cannot access account transactions', function () {
    $this->get(route('accounts.transactions', $this->account))
        ->assertRedirect(route('login'));
});

test('user cannot access another users account transactions', function () {
    $otherAccount = Account::factory()->create();

    $this->actingAs($this->user)
        ->get(route('accounts.transactions', $otherAccount))
        ->assertNotFound();
});

test('authenticated user can view their account transactions', function () {
    $this->actingAs($this->user)
        ->get(route('accounts.transactions', $this->account))
        ->assertSuccessful()
        ->assertViewIs('accounts.transactions')
        ->assertViewHas('account')
        ->assertViewHas('transactions')
        ->assertViewHas('filters');
});

test('transactions include incomes for the account', function () {
    $income = Income::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create(['description' => 'Test Income', 'transacted_at' => now()]);

    $this->actingAs($this->user)
        ->get(route('accounts.transactions', $this->account))
        ->assertSuccessful()
        ->assertSee('Test Income')
        ->assertSee('Income');
});

test('transactions include expenses for the account', function () {
    $expense = Expense::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create(['description' => 'Test Expense', 'transacted_at' => now()]);

    $this->actingAs($this->user)
        ->get(route('accounts.transactions', $this->account))
        ->assertSuccessful()
        ->assertSee('Test Expense')
        ->assertSee('Expense');
});

test('transactions include transfers where account is creditor', function () {
    $otherAccount = Account::factory()->forUser($this->user)->create();

    $transfer = Transfer::factory()
        ->forUser($this->user)
        ->create([
            'creditor_id' => $this->account->id,
            'debtor_id' => $otherAccount->id,
            'description' => 'Transfer In Test',
            'transacted_at' => now(),
        ]);

    $this->actingAs($this->user)
        ->get(route('accounts.transactions', $this->account))
        ->assertSuccessful()
        ->assertSee('Transfer In Test')
        ->assertSee('Transfer In');
});

test('transactions include transfers where account is debtor', function () {
    $otherAccount = Account::factory()->forUser($this->user)->create();

    $transfer = Transfer::factory()
        ->forUser($this->user)
        ->create([
            'creditor_id' => $otherAccount->id,
            'debtor_id' => $this->account->id,
            'description' => 'Transfer Out Test',
            'transacted_at' => now(),
        ]);

    $this->actingAs($this->user)
        ->get(route('accounts.transactions', $this->account))
        ->assertSuccessful()
        ->assertSee('Transfer Out Test')
        ->assertSee('Transfer Out');
});

test('transactions do not include other accounts transactions', function () {
    $otherAccount = Account::factory()->forUser($this->user)->create();

    $ownIncome = Income::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create(['description' => 'My Income', 'transacted_at' => now()]);

    $otherIncome = Income::factory()
        ->forUser($this->user)
        ->forAccount($otherAccount)
        ->create(['description' => 'Other Account Income', 'transacted_at' => now()]);

    $this->actingAs($this->user)
        ->get(route('accounts.transactions', $this->account))
        ->assertSuccessful()
        ->assertSee('My Income')
        ->assertDontSee('Other Account Income');
});

test('can filter transactions by date range', function () {
    $oldIncome = Income::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create(['description' => 'Old Income', 'transacted_at' => now()->subMonth()]);

    $recentExpense = Expense::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create(['description' => 'Recent Expense', 'transacted_at' => now()]);

    $this->actingAs($this->user)
        ->get(route('accounts.transactions', [
            'account' => $this->account,
            'filter' => [
                'from_date' => now()->subDays(7)->format('Y-m-d'),
                'to_date' => now()->addDay()->format('Y-m-d'),
            ],
        ]))
        ->assertSuccessful()
        ->assertSee('Recent Expense')
        ->assertDontSee('Old Income');
});

test('can filter transactions by search term', function () {
    $matchingIncome = Income::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create(['description' => 'Salary payment', 'transacted_at' => now()]);

    $nonMatchingExpense = Expense::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create(['description' => 'Grocery shopping', 'transacted_at' => now()]);

    $this->actingAs($this->user)
        ->get(route('accounts.transactions', [
            'account' => $this->account,
            'filter' => ['search' => 'Salary'],
        ]))
        ->assertSuccessful()
        ->assertSee('Salary payment')
        ->assertDontSee('Grocery shopping');
});

test('can filter transactions by type income', function () {
    $income = Income::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create(['description' => 'Test Income', 'transacted_at' => now()]);

    $expense = Expense::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create(['description' => 'Test Expense', 'transacted_at' => now()]);

    $this->actingAs($this->user)
        ->get(route('accounts.transactions', [
            'account' => $this->account,
            'filter' => ['type' => 'income'],
        ]))
        ->assertSuccessful()
        ->assertSee('Test Income')
        ->assertDontSee('Test Expense');
});

test('can filter transactions by type expense', function () {
    $income = Income::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create(['description' => 'Test Income', 'transacted_at' => now()]);

    $expense = Expense::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create(['description' => 'Test Expense', 'transacted_at' => now()]);

    $this->actingAs($this->user)
        ->get(route('accounts.transactions', [
            'account' => $this->account,
            'filter' => ['type' => 'expense'],
        ]))
        ->assertSuccessful()
        ->assertSee('Test Expense')
        ->assertDontSee('Test Income');
});

test('can filter transactions by type transfer', function () {
    $otherAccount = Account::factory()->forUser($this->user)->create();

    $income = Income::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create(['description' => 'Test Income', 'transacted_at' => now()]);

    $transfer = Transfer::factory()
        ->forUser($this->user)
        ->create([
            'creditor_id' => $this->account->id,
            'debtor_id' => $otherAccount->id,
            'description' => 'Test Transfer',
            'transacted_at' => now(),
        ]);

    $this->actingAs($this->user)
        ->get(route('accounts.transactions', [
            'account' => $this->account,
            'filter' => ['type' => 'transfer'],
        ]))
        ->assertSuccessful()
        ->assertSee('Test Transfer')
        ->assertDontSee('Test Income');
});

test('transactions default to current month date range', function () {
    $this->actingAs($this->user)
        ->get(route('accounts.transactions', $this->account))
        ->assertSuccessful()
        ->assertViewHas('filters', function ($filters) {
            return $filters['from_date'] === now()->startOfMonth()->format('Y-m-d')
                && $filters['to_date'] === now()->endOfMonth()->format('Y-m-d');
        });
});

test('transactions show person name when available', function () {
    $person = Person::factory()->create(['name' => 'John Doe']);

    $income = Income::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create([
            'description' => 'Payment from John',
            'person_id' => $person->id,
            'transacted_at' => now(),
        ]);

    $this->actingAs($this->user)
        ->get(route('accounts.transactions', $this->account))
        ->assertSuccessful()
        ->assertSee('John Doe');
});

test('transactions show related account name for transfers', function () {
    $otherAccount = Account::factory()->forUser($this->user)->create(['name' => 'Savings Account']);

    $transfer = Transfer::factory()
        ->forUser($this->user)
        ->create([
            'creditor_id' => $this->account->id,
            'debtor_id' => $otherAccount->id,
            'description' => 'Transfer from savings',
            'transacted_at' => now(),
        ]);

    $this->actingAs($this->user)
        ->get(route('accounts.transactions', $this->account))
        ->assertSuccessful()
        ->assertSee('Savings Account');
});
