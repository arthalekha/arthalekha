<?php

use App\Enums\Frequency;
use App\Models\Account;
use App\Models\Person;
use App\Models\RecurringExpense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->account = Account::factory()->forUser($this->user)->create();
});

test('guest cannot access recurring expenses index', function () {
    $this->get(route('recurring-expenses.index'))
        ->assertRedirect(route('login'));
});

test('authenticated user can view recurring expenses index', function () {
    RecurringExpense::factory()->count(3)->forUser($this->user)->forAccount($this->account)->create();

    $this->actingAs($this->user)
        ->get(route('recurring-expenses.index'))
        ->assertSuccessful()
        ->assertViewIs('recurring-expenses.index')
        ->assertViewHas('recurringExpenses');
});

test('user can only see their own recurring expenses', function () {
    $ownRecurringExpense = RecurringExpense::factory()->forUser($this->user)->forAccount($this->account)->create();
    $otherRecurringExpense = RecurringExpense::factory()->create();

    $this->actingAs($this->user)
        ->get(route('recurring-expenses.index'))
        ->assertSuccessful()
        ->assertSee($ownRecurringExpense->description)
        ->assertDontSee($otherRecurringExpense->description);
});

test('authenticated user can view create recurring expense form', function () {
    $this->actingAs($this->user)
        ->get(route('recurring-expenses.create'))
        ->assertSuccessful()
        ->assertViewIs('recurring-expenses.create')
        ->assertViewHas('accounts')
        ->assertViewHas('people')
        ->assertViewHas('frequencies');
});

test('authenticated user can create a recurring expense', function () {
    $recurringExpenseData = [
        'account_id' => $this->account->id,
        'description' => 'Monthly Rent',
        'next_transaction_at' => now()->addMonth()->format('Y-m-d H:i:s'),
        'amount' => 1500.00,
        'frequency' => Frequency::Monthly->value,
        'remaining_recurrences' => 12,
    ];

    $this->actingAs($this->user)
        ->post(route('recurring-expenses.store'), $recurringExpenseData)
        ->assertRedirect(route('recurring-expenses.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('recurring_expenses', [
        'description' => 'Monthly Rent',
        'amount' => 1500.00,
        'frequency' => Frequency::Monthly->value,
    ]);
});

test('recurring expense can be created with unlimited recurrences', function () {
    $recurringExpenseData = [
        'account_id' => $this->account->id,
        'description' => 'Unlimited Expense',
        'next_transaction_at' => now()->addMonth()->format('Y-m-d H:i:s'),
        'amount' => 1000.00,
        'frequency' => Frequency::Weekly->value,
        'remaining_recurrences' => null,
    ];

    $this->actingAs($this->user)
        ->post(route('recurring-expenses.store'), $recurringExpenseData)
        ->assertRedirect(route('recurring-expenses.index'));

    $this->assertDatabaseHas('recurring_expenses', [
        'description' => 'Unlimited Expense',
        'remaining_recurrences' => null,
    ]);
});

test('recurring expense can be associated with a person', function () {
    $person = Person::factory()->create();

    $recurringExpenseData = [
        'account_id' => $this->account->id,
        'person_id' => $person->id,
        'description' => 'Subscription Payment',
        'next_transaction_at' => now()->addMonth()->format('Y-m-d H:i:s'),
        'amount' => 200.00,
        'frequency' => Frequency::Monthly->value,
    ];

    $this->actingAs($this->user)
        ->post(route('recurring-expenses.store'), $recurringExpenseData)
        ->assertRedirect(route('recurring-expenses.index'));

    $this->assertDatabaseHas('recurring_expenses', [
        'description' => 'Subscription Payment',
        'person_id' => $person->id,
    ]);
});

test('creating a recurring expense requires a description', function () {
    $recurringExpenseData = [
        'account_id' => $this->account->id,
        'next_transaction_at' => now()->addMonth()->format('Y-m-d H:i:s'),
        'amount' => 1000.00,
        'frequency' => Frequency::Monthly->value,
    ];

    $this->actingAs($this->user)
        ->post(route('recurring-expenses.store'), $recurringExpenseData)
        ->assertSessionHasErrors('description');
});

test('creating a recurring expense requires a valid amount', function () {
    $recurringExpenseData = [
        'account_id' => $this->account->id,
        'description' => 'Test',
        'next_transaction_at' => now()->addMonth()->format('Y-m-d H:i:s'),
        'amount' => 0,
        'frequency' => Frequency::Monthly->value,
    ];

    $this->actingAs($this->user)
        ->post(route('recurring-expenses.store'), $recurringExpenseData)
        ->assertSessionHasErrors('amount');
});

test('creating a recurring expense requires a valid frequency', function () {
    $recurringExpenseData = [
        'account_id' => $this->account->id,
        'description' => 'Test',
        'next_transaction_at' => now()->addMonth()->format('Y-m-d H:i:s'),
        'amount' => 1000.00,
        'frequency' => 'invalid',
    ];

    $this->actingAs($this->user)
        ->post(route('recurring-expenses.store'), $recurringExpenseData)
        ->assertSessionHasErrors('frequency');
});

test('creating a recurring expense requires users own account', function () {
    $otherAccount = Account::factory()->create();

    $recurringExpenseData = [
        'account_id' => $otherAccount->id,
        'description' => 'Test',
        'next_transaction_at' => now()->addMonth()->format('Y-m-d H:i:s'),
        'amount' => 1000.00,
        'frequency' => Frequency::Monthly->value,
    ];

    $this->actingAs($this->user)
        ->post(route('recurring-expenses.store'), $recurringExpenseData)
        ->assertSessionHasErrors('account_id');
});

test('authenticated user can view their own recurring expense', function () {
    $recurringExpense = RecurringExpense::factory()->forUser($this->user)->forAccount($this->account)->create();

    $this->actingAs($this->user)
        ->get(route('recurring-expenses.show', $recurringExpense))
        ->assertSuccessful()
        ->assertViewIs('recurring-expenses.show')
        ->assertSee($recurringExpense->description);
});

test('user cannot view another users recurring expense', function () {
    $otherRecurringExpense = RecurringExpense::factory()->create();

    $this->actingAs($this->user)
        ->get(route('recurring-expenses.show', $otherRecurringExpense))
        ->assertNotFound();
});

test('authenticated user can view edit form for their own recurring expense', function () {
    $recurringExpense = RecurringExpense::factory()->forUser($this->user)->forAccount($this->account)->create();

    $this->actingAs($this->user)
        ->get(route('recurring-expenses.edit', $recurringExpense))
        ->assertSuccessful()
        ->assertViewIs('recurring-expenses.edit')
        ->assertViewHas('recurringExpense');
});

test('authenticated user can update their own recurring expense', function () {
    $recurringExpense = RecurringExpense::factory()->forUser($this->user)->forAccount($this->account)->create();

    $updatedData = [
        'account_id' => $this->account->id,
        'description' => 'Updated Recurring Expense',
        'next_transaction_at' => now()->addMonth()->format('Y-m-d H:i:s'),
        'amount' => 3000.00,
        'frequency' => Frequency::Weekly->value,
    ];

    $this->actingAs($this->user)
        ->put(route('recurring-expenses.update', $recurringExpense), $updatedData)
        ->assertRedirect(route('recurring-expenses.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('recurring_expenses', [
        'id' => $recurringExpense->id,
        'description' => 'Updated Recurring Expense',
    ]);
});

test('user cannot update another users recurring expense', function () {
    $otherRecurringExpense = RecurringExpense::factory()->create();

    $this->actingAs($this->user)
        ->put(route('recurring-expenses.update', $otherRecurringExpense), [
            'account_id' => $this->account->id,
            'description' => 'Hacked',
            'next_transaction_at' => now()->addMonth()->format('Y-m-d H:i:s'),
            'amount' => 100,
            'frequency' => Frequency::Monthly->value,
        ])
        ->assertNotFound();
});

test('authenticated user can delete their own recurring expense', function () {
    $recurringExpense = RecurringExpense::factory()->forUser($this->user)->forAccount($this->account)->create();

    $this->actingAs($this->user)
        ->delete(route('recurring-expenses.destroy', $recurringExpense))
        ->assertRedirect(route('recurring-expenses.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('recurring_expenses', ['id' => $recurringExpense->id]);
});

test('user cannot delete another users recurring expense', function () {
    $otherRecurringExpense = RecurringExpense::factory()->create();

    $this->actingAs($this->user)
        ->delete(route('recurring-expenses.destroy', $otherRecurringExpense))
        ->assertNotFound();

    $this->assertDatabaseHas('recurring_expenses', ['id' => $otherRecurringExpense->id]);
});

test('can filter recurring expenses by search term', function () {
    $matchingExpense = RecurringExpense::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create(['description' => 'Monthly Rent']);

    $nonMatchingExpense = RecurringExpense::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create(['description' => 'Grocery Shopping']);

    $this->actingAs($this->user)
        ->get(route('recurring-expenses.index', ['filter' => ['search' => 'Rent']]))
        ->assertSuccessful()
        ->assertSee('Monthly Rent')
        ->assertDontSee('Grocery Shopping');
});

test('can filter recurring expenses by frequency', function () {
    $monthlyExpense = RecurringExpense::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create(['description' => 'Monthly Expense', 'frequency' => Frequency::Monthly]);

    $weeklyExpense = RecurringExpense::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create(['description' => 'Weekly Expense', 'frequency' => Frequency::Weekly]);

    $this->actingAs($this->user)
        ->get(route('recurring-expenses.index', ['filter' => ['frequency' => Frequency::Monthly->value]]))
        ->assertSuccessful()
        ->assertSee('Monthly Expense')
        ->assertDontSee('Weekly Expense');
});
