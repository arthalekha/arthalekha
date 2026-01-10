<?php

use App\Enums\Frequency;
use App\Models\Account;
use App\Models\Person;
use App\Models\RecurringIncome;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->account = Account::factory()->forUser($this->user)->create();
});

test('guest cannot access recurring incomes index', function () {
    $this->get(route('recurring-incomes.index'))
        ->assertRedirect(route('login'));
});

test('authenticated user can view recurring incomes index', function () {
    RecurringIncome::factory()->count(3)->forUser($this->user)->forAccount($this->account)->create();

    $this->actingAs($this->user)
        ->get(route('recurring-incomes.index'))
        ->assertSuccessful()
        ->assertViewIs('recurring-incomes.index')
        ->assertViewHas('recurringIncomes');
});

test('user can only see their own recurring incomes', function () {
    $ownRecurringIncome = RecurringIncome::factory()->forUser($this->user)->forAccount($this->account)->create();
    $otherRecurringIncome = RecurringIncome::factory()->create();

    $this->actingAs($this->user)
        ->get(route('recurring-incomes.index'))
        ->assertSuccessful()
        ->assertSee($ownRecurringIncome->description)
        ->assertDontSee($otherRecurringIncome->description);
});

test('authenticated user can view create recurring income form', function () {
    $this->actingAs($this->user)
        ->get(route('recurring-incomes.create'))
        ->assertSuccessful()
        ->assertViewIs('recurring-incomes.create')
        ->assertViewHas('accounts')
        ->assertViewHas('people')
        ->assertViewHas('frequencies');
});

test('authenticated user can create a recurring income', function () {
    $recurringIncomeData = [
        'account_id' => $this->account->id,
        'description' => 'Monthly Salary',
        'next_transaction_at' => now()->addMonth()->format('Y-m-d H:i:s'),
        'amount' => 5000.00,
        'frequency' => Frequency::Monthly->value,
        'remaining_recurrences' => 12,
    ];

    $this->actingAs($this->user)
        ->post(route('recurring-incomes.store'), $recurringIncomeData)
        ->assertRedirect(route('recurring-incomes.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('recurring_incomes', [
        'description' => 'Monthly Salary',
        'amount' => 5000.00,
        'frequency' => Frequency::Monthly->value,
    ]);
});

test('recurring income can be created with unlimited recurrences', function () {
    $recurringIncomeData = [
        'account_id' => $this->account->id,
        'description' => 'Unlimited Income',
        'next_transaction_at' => now()->addMonth()->format('Y-m-d H:i:s'),
        'amount' => 1000.00,
        'frequency' => Frequency::Weekly->value,
        'remaining_recurrences' => null,
    ];

    $this->actingAs($this->user)
        ->post(route('recurring-incomes.store'), $recurringIncomeData)
        ->assertRedirect(route('recurring-incomes.index'));

    $this->assertDatabaseHas('recurring_incomes', [
        'description' => 'Unlimited Income',
        'remaining_recurrences' => null,
    ]);
});

test('recurring income can be associated with a person', function () {
    $person = Person::factory()->create();

    $recurringIncomeData = [
        'account_id' => $this->account->id,
        'person_id' => $person->id,
        'description' => 'Rental Income',
        'next_transaction_at' => now()->addMonth()->format('Y-m-d H:i:s'),
        'amount' => 2000.00,
        'frequency' => Frequency::Monthly->value,
    ];

    $this->actingAs($this->user)
        ->post(route('recurring-incomes.store'), $recurringIncomeData)
        ->assertRedirect(route('recurring-incomes.index'));

    $this->assertDatabaseHas('recurring_incomes', [
        'description' => 'Rental Income',
        'person_id' => $person->id,
    ]);
});

test('creating a recurring income requires a description', function () {
    $recurringIncomeData = [
        'account_id' => $this->account->id,
        'next_transaction_at' => now()->addMonth()->format('Y-m-d H:i:s'),
        'amount' => 1000.00,
        'frequency' => Frequency::Monthly->value,
    ];

    $this->actingAs($this->user)
        ->post(route('recurring-incomes.store'), $recurringIncomeData)
        ->assertSessionHasErrors('description');
});

test('creating a recurring income requires a valid amount', function () {
    $recurringIncomeData = [
        'account_id' => $this->account->id,
        'description' => 'Test',
        'next_transaction_at' => now()->addMonth()->format('Y-m-d H:i:s'),
        'amount' => 0,
        'frequency' => Frequency::Monthly->value,
    ];

    $this->actingAs($this->user)
        ->post(route('recurring-incomes.store'), $recurringIncomeData)
        ->assertSessionHasErrors('amount');
});

test('creating a recurring income requires a valid frequency', function () {
    $recurringIncomeData = [
        'account_id' => $this->account->id,
        'description' => 'Test',
        'next_transaction_at' => now()->addMonth()->format('Y-m-d H:i:s'),
        'amount' => 1000.00,
        'frequency' => 'invalid',
    ];

    $this->actingAs($this->user)
        ->post(route('recurring-incomes.store'), $recurringIncomeData)
        ->assertSessionHasErrors('frequency');
});

test('creating a recurring income requires users own account', function () {
    $otherAccount = Account::factory()->create();

    $recurringIncomeData = [
        'account_id' => $otherAccount->id,
        'description' => 'Test',
        'next_transaction_at' => now()->addMonth()->format('Y-m-d H:i:s'),
        'amount' => 1000.00,
        'frequency' => Frequency::Monthly->value,
    ];

    $this->actingAs($this->user)
        ->post(route('recurring-incomes.store'), $recurringIncomeData)
        ->assertSessionHasErrors('account_id');
});

test('authenticated user can view their own recurring income', function () {
    $recurringIncome = RecurringIncome::factory()->forUser($this->user)->forAccount($this->account)->create();

    $this->actingAs($this->user)
        ->get(route('recurring-incomes.show', $recurringIncome))
        ->assertSuccessful()
        ->assertViewIs('recurring-incomes.show')
        ->assertSee($recurringIncome->description);
});

test('user cannot view another users recurring income', function () {
    $otherRecurringIncome = RecurringIncome::factory()->create();

    $this->actingAs($this->user)
        ->get(route('recurring-incomes.show', $otherRecurringIncome))
        ->assertForbidden();
});

test('authenticated user can view edit form for their own recurring income', function () {
    $recurringIncome = RecurringIncome::factory()->forUser($this->user)->forAccount($this->account)->create();

    $this->actingAs($this->user)
        ->get(route('recurring-incomes.edit', $recurringIncome))
        ->assertSuccessful()
        ->assertViewIs('recurring-incomes.edit')
        ->assertViewHas('recurringIncome');
});

test('authenticated user can update their own recurring income', function () {
    $recurringIncome = RecurringIncome::factory()->forUser($this->user)->forAccount($this->account)->create();

    $updatedData = [
        'account_id' => $this->account->id,
        'description' => 'Updated Recurring Income',
        'next_transaction_at' => now()->addMonth()->format('Y-m-d H:i:s'),
        'amount' => 3000.00,
        'frequency' => Frequency::Weekly->value,
    ];

    $this->actingAs($this->user)
        ->put(route('recurring-incomes.update', $recurringIncome), $updatedData)
        ->assertRedirect(route('recurring-incomes.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('recurring_incomes', [
        'id' => $recurringIncome->id,
        'description' => 'Updated Recurring Income',
    ]);
});

test('user cannot update another users recurring income', function () {
    $otherRecurringIncome = RecurringIncome::factory()->create();

    $this->actingAs($this->user)
        ->put(route('recurring-incomes.update', $otherRecurringIncome), [
            'account_id' => $this->account->id,
            'description' => 'Hacked',
            'next_transaction_at' => now()->addMonth()->format('Y-m-d H:i:s'),
            'amount' => 100,
            'frequency' => Frequency::Monthly->value,
        ])
        ->assertForbidden();
});

test('authenticated user can delete their own recurring income', function () {
    $recurringIncome = RecurringIncome::factory()->forUser($this->user)->forAccount($this->account)->create();

    $this->actingAs($this->user)
        ->delete(route('recurring-incomes.destroy', $recurringIncome))
        ->assertRedirect(route('recurring-incomes.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('recurring_incomes', ['id' => $recurringIncome->id]);
});

test('user cannot delete another users recurring income', function () {
    $otherRecurringIncome = RecurringIncome::factory()->create();

    $this->actingAs($this->user)
        ->delete(route('recurring-incomes.destroy', $otherRecurringIncome))
        ->assertForbidden();

    $this->assertDatabaseHas('recurring_incomes', ['id' => $otherRecurringIncome->id]);
});

test('can filter recurring incomes by search term', function () {
    $matchingIncome = RecurringIncome::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create(['description' => 'Monthly Salary']);

    $nonMatchingIncome = RecurringIncome::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create(['description' => 'Freelance Work']);

    $this->actingAs($this->user)
        ->get(route('recurring-incomes.index', ['filter' => ['search' => 'Salary']]))
        ->assertSuccessful()
        ->assertSee('Monthly Salary')
        ->assertDontSee('Freelance Work');
});

test('can filter recurring incomes by frequency', function () {
    $monthlyIncome = RecurringIncome::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create(['description' => 'Monthly Income', 'frequency' => Frequency::Monthly]);

    $weeklyIncome = RecurringIncome::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create(['description' => 'Weekly Income', 'frequency' => Frequency::Weekly]);

    $this->actingAs($this->user)
        ->get(route('recurring-incomes.index', ['filter' => ['frequency' => Frequency::Monthly->value]]))
        ->assertSuccessful()
        ->assertSee('Monthly Income')
        ->assertDontSee('Weekly Income');
});
