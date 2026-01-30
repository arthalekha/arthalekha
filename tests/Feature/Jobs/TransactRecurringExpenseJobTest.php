<?php

use App\Enums\Frequency;
use App\Jobs\TransactRecurringExpenseJob;
use App\Models\Account;
use App\Models\Expense;
use App\Models\RecurringExpense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->account = Account::factory()->forUser($this->user)->create();
});

test('recurring expense creates expense entry when account is set', function () {
    $recurringExpense = RecurringExpense::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create([
            'next_transaction_at' => now()->subDay(),
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => null,
        ]);

    TransactRecurringExpenseJob::dispatch();

    expect(Expense::where('user_id', $this->user->id)->count())->toBe(1);

    $expense = Expense::where('user_id', $this->user->id)->first();
    expect($expense->account_id)->toBe($this->account->id);
    expect($expense->description)->toBe($recurringExpense->description);
});

test('recurring expense skips expense entry when account is null', function () {
    $recurringExpense = RecurringExpense::factory()
        ->forUser($this->user)
        ->withoutAccount()
        ->create([
            'next_transaction_at' => now()->subDay(),
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => null,
        ]);

    TransactRecurringExpenseJob::dispatch();

    expect(Expense::where('user_id', $this->user->id)->count())->toBe(0);
});

test('recurring expense updates next transaction date when account is null', function () {
    $originalNextDate = now()->subDay();

    $recurringExpense = RecurringExpense::factory()
        ->forUser($this->user)
        ->withoutAccount()
        ->create([
            'next_transaction_at' => $originalNextDate,
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => null,
        ]);

    TransactRecurringExpenseJob::dispatch();

    $recurringExpense->refresh();

    expect($recurringExpense->next_transaction_at->format('Y-m-d'))
        ->toBe($originalNextDate->addMonth()->format('Y-m-d'));
});

test('recurring expense decrements remaining recurrences when account is null', function () {
    $recurringExpense = RecurringExpense::factory()
        ->forUser($this->user)
        ->withoutAccount()
        ->create([
            'next_transaction_at' => now()->subDay(),
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => 5,
        ]);

    TransactRecurringExpenseJob::dispatch();

    $recurringExpense->refresh();

    expect($recurringExpense->remaining_recurrences)->toBe(4);
    expect(Expense::where('user_id', $this->user->id)->count())->toBe(0);
});

test('recurring expense is deleted when remaining recurrences reaches zero with null account', function () {
    $recurringExpense = RecurringExpense::factory()
        ->forUser($this->user)
        ->withoutAccount()
        ->create([
            'next_transaction_at' => now()->subDay(),
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => 1,
        ]);

    TransactRecurringExpenseJob::dispatch();

    expect(RecurringExpense::find($recurringExpense->id))->toBeNull();
    expect(Expense::where('user_id', $this->user->id)->count())->toBe(0);
});
