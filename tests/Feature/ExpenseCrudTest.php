<?php

use App\Models\Account;
use App\Models\Expense;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->account = Account::factory()->forUser($this->user)->create();
});

test('guest cannot access expenses index', function () {
    $this->get(route('expenses.index'))
        ->assertRedirect(route('login'));
});

test('authenticated user can view expenses index', function () {
    Expense::factory()->count(3)->forUser($this->user)->forAccount($this->account)->create();

    $this->actingAs($this->user)
        ->get(route('expenses.index'))
        ->assertSuccessful()
        ->assertViewIs('expenses.index')
        ->assertViewHas('expenses');
});

test('user can only see their own expenses', function () {
    $ownExpense = Expense::factory()->forUser($this->user)->forAccount($this->account)->create();
    $otherExpense = Expense::factory()->create();

    $this->actingAs($this->user)
        ->get(route('expenses.index'))
        ->assertSuccessful()
        ->assertSee($ownExpense->description)
        ->assertDontSee($otherExpense->description);
});

test('authenticated user can view create expense form', function () {
    $this->actingAs($this->user)
        ->get(route('expenses.create'))
        ->assertSuccessful()
        ->assertViewIs('expenses.create')
        ->assertViewHas('accounts')
        ->assertViewHas('people');
});

test('authenticated user can create an expense', function () {
    $expenseData = [
        'account_id' => $this->account->id,
        'description' => 'Test Expense',
        'transacted_at' => now()->format('Y-m-d H:i:s'),
        'amount' => 500.50,
    ];

    $this->actingAs($this->user)
        ->post(route('expenses.store'), $expenseData)
        ->assertRedirect(route('expenses.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('expenses', [
        'description' => 'Test Expense',
        'user_id' => $this->user->id,
        'account_id' => $this->account->id,
    ]);
});

test('expense can be associated with a person', function () {
    $person = Person::factory()->create();

    $expenseData = [
        'account_id' => $this->account->id,
        'person_id' => $person->id,
        'description' => 'Payment to person',
        'transacted_at' => now()->format('Y-m-d H:i:s'),
        'amount' => 250.00,
    ];

    $this->actingAs($this->user)
        ->post(route('expenses.store'), $expenseData)
        ->assertRedirect(route('expenses.index'));

    $this->assertDatabaseHas('expenses', [
        'description' => 'Payment to person',
        'person_id' => $person->id,
    ]);
});

test('creating an expense requires a description', function () {
    $this->actingAs($this->user)
        ->post(route('expenses.store'), [
            'account_id' => $this->account->id,
            'description' => '',
            'transacted_at' => now()->format('Y-m-d H:i:s'),
            'amount' => 100,
        ])
        ->assertSessionHasErrors('description');
});

test('creating an expense requires a valid amount', function () {
    $this->actingAs($this->user)
        ->post(route('expenses.store'), [
            'account_id' => $this->account->id,
            'description' => 'Test',
            'transacted_at' => now()->format('Y-m-d H:i:s'),
            'amount' => 0,
        ])
        ->assertSessionHasErrors('amount');
});

test('creating an expense requires users own account', function () {
    $otherAccount = Account::factory()->create();

    $this->actingAs($this->user)
        ->post(route('expenses.store'), [
            'account_id' => $otherAccount->id,
            'description' => 'Test',
            'transacted_at' => now()->format('Y-m-d H:i:s'),
            'amount' => 100,
        ])
        ->assertSessionHasErrors('account_id');
});

test('authenticated user can view their own expense', function () {
    $expense = Expense::factory()->forUser($this->user)->forAccount($this->account)->create();

    $this->actingAs($this->user)
        ->get(route('expenses.show', $expense))
        ->assertSuccessful()
        ->assertViewIs('expenses.show')
        ->assertViewHas('expense');
});

test('user cannot view another users expense', function () {
    $otherExpense = Expense::factory()->create();

    $this->actingAs($this->user)
        ->get(route('expenses.show', $otherExpense))
        ->assertForbidden();
});

test('authenticated user can view edit form for their own expense', function () {
    $expense = Expense::factory()->forUser($this->user)->forAccount($this->account)->create();

    $this->actingAs($this->user)
        ->get(route('expenses.edit', $expense))
        ->assertSuccessful()
        ->assertViewIs('expenses.edit')
        ->assertViewHas('expense');
});

test('authenticated user can update their own expense', function () {
    $expense = Expense::factory()->forUser($this->user)->forAccount($this->account)->create();

    $updatedData = [
        'account_id' => $this->account->id,
        'description' => 'Updated Expense',
        'transacted_at' => now()->format('Y-m-d H:i:s'),
        'amount' => 1500.00,
    ];

    $this->actingAs($this->user)
        ->put(route('expenses.update', $expense), $updatedData)
        ->assertRedirect(route('expenses.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('expenses', [
        'id' => $expense->id,
        'description' => 'Updated Expense',
    ]);
});

test('user cannot update another users expense', function () {
    $otherExpense = Expense::factory()->create();

    $this->actingAs($this->user)
        ->put(route('expenses.update', $otherExpense), [
            'account_id' => $this->account->id,
            'description' => 'Hacked',
            'transacted_at' => now()->format('Y-m-d H:i:s'),
            'amount' => 100,
        ])
        ->assertForbidden();
});

test('authenticated user can delete their own expense', function () {
    $expense = Expense::factory()->forUser($this->user)->forAccount($this->account)->create();

    $this->actingAs($this->user)
        ->delete(route('expenses.destroy', $expense))
        ->assertRedirect(route('expenses.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
});

test('user cannot delete another users expense', function () {
    $otherExpense = Expense::factory()->create();

    $this->actingAs($this->user)
        ->delete(route('expenses.destroy', $otherExpense))
        ->assertForbidden();

    $this->assertDatabaseHas('expenses', ['id' => $otherExpense->id]);
});
