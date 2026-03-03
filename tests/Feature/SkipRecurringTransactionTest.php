<?php

use App\Enums\Frequency;
use App\Models\Expense;
use App\Models\Income;
use App\Models\RecurringExpense;
use App\Models\RecurringIncome;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('skip income advances next_transaction_at without creating income', function () {
    $originalDate = now()->subDay();

    $recurringIncome = RecurringIncome::factory()
        ->forUser($this->user)
        ->withoutAccount()
        ->create([
            'next_transaction_at' => $originalDate,
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => null,
        ]);

    $response = $this->post(route('recurring-incomes.skip', $recurringIncome));

    $response->assertRedirect(route('recurring-transactions.dashboard'));
    $response->assertSessionHas('success');

    $recurringIncome->refresh();

    expect($recurringIncome->next_transaction_at->format('Y-m-d'))
        ->toBe($originalDate->copy()->addMonth()->format('Y-m-d'));
    expect(Income::where('user_id', $this->user->id)->count())->toBe(0);
});

test('skip income decrements remaining recurrences', function () {
    $recurringIncome = RecurringIncome::factory()
        ->forUser($this->user)
        ->withoutAccount()
        ->create([
            'next_transaction_at' => now()->subDay(),
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => 5,
        ]);

    $this->post(route('recurring-incomes.skip', $recurringIncome));

    $recurringIncome->refresh();

    expect($recurringIncome->remaining_recurrences)->toBe(4);
});

test('skip income deletes when recurrences exhausted', function () {
    $recurringIncome = RecurringIncome::factory()
        ->forUser($this->user)
        ->withoutAccount()
        ->create([
            'next_transaction_at' => now()->subDay(),
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => 1,
        ]);

    $this->post(route('recurring-incomes.skip', $recurringIncome));

    expect(RecurringIncome::find($recurringIncome->id))->toBeNull();
    expect(Income::where('user_id', $this->user->id)->count())->toBe(0);
});

test('skip expense advances next_transaction_at without creating expense', function () {
    $originalDate = now()->subDay();

    $recurringExpense = RecurringExpense::factory()
        ->forUser($this->user)
        ->withoutAccount()
        ->create([
            'next_transaction_at' => $originalDate,
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => null,
        ]);

    $response = $this->post(route('recurring-expenses.skip', $recurringExpense));

    $response->assertRedirect(route('recurring-transactions.dashboard'));
    $response->assertSessionHas('success');

    $recurringExpense->refresh();

    expect($recurringExpense->next_transaction_at->format('Y-m-d'))
        ->toBe($originalDate->copy()->addMonth()->format('Y-m-d'));
    expect(Expense::where('user_id', $this->user->id)->count())->toBe(0);
});

test('skip expense decrements remaining recurrences', function () {
    $recurringExpense = RecurringExpense::factory()
        ->forUser($this->user)
        ->withoutAccount()
        ->create([
            'next_transaction_at' => now()->subDay(),
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => 5,
        ]);

    $this->post(route('recurring-expenses.skip', $recurringExpense));

    $recurringExpense->refresh();

    expect($recurringExpense->remaining_recurrences)->toBe(4);
});

test('skip expense deletes when recurrences exhausted', function () {
    $recurringExpense = RecurringExpense::factory()
        ->forUser($this->user)
        ->withoutAccount()
        ->create([
            'next_transaction_at' => now()->subDay(),
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => 1,
        ]);

    $this->post(route('recurring-expenses.skip', $recurringExpense));

    expect(RecurringExpense::find($recurringExpense->id))->toBeNull();
    expect(Expense::where('user_id', $this->user->id)->count())->toBe(0);
});
