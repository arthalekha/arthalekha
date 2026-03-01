<?php

use App\Enums\Frequency;
use App\Jobs\TransactRecurringIncomeJob;
use App\Models\Account;
use App\Models\Income;
use App\Models\RecurringIncome;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->account = Account::factory()->forUser($this->user)->create();
});

test('recurring income creates income entry when account is set', function () {
    $recurringIncome = RecurringIncome::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create([
            'next_transaction_at' => now()->subDay(),
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => null,
        ]);

    TransactRecurringIncomeJob::dispatch();

    expect(Income::where('user_id', $this->user->id)->count())->toBe(1);

    $income = Income::where('user_id', $this->user->id)->first();
    expect($income->account_id)->toBe($this->account->id);
    expect($income->description)->toBe($recurringIncome->description);
});

test('recurring income skips income entry when account is null', function () {
    $recurringIncome = RecurringIncome::factory()
        ->forUser($this->user)
        ->withoutAccount()
        ->create([
            'next_transaction_at' => now()->subDay(),
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => null,
        ]);

    TransactRecurringIncomeJob::dispatch();

    expect(Income::where('user_id', $this->user->id)->count())->toBe(0);
});

test('recurring income does not advance next transaction date when account is null', function () {
    $originalNextDate = now()->subDay();

    $recurringIncome = RecurringIncome::factory()
        ->forUser($this->user)
        ->withoutAccount()
        ->create([
            'next_transaction_at' => $originalNextDate,
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => null,
        ]);

    TransactRecurringIncomeJob::dispatch();

    $recurringIncome->refresh();

    expect($recurringIncome->next_transaction_at->format('Y-m-d'))
        ->toBe($originalNextDate->format('Y-m-d'));
});

test('recurring income does not decrement remaining recurrences when account is null', function () {
    $recurringIncome = RecurringIncome::factory()
        ->forUser($this->user)
        ->withoutAccount()
        ->create([
            'next_transaction_at' => now()->subDay(),
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => 5,
        ]);

    TransactRecurringIncomeJob::dispatch();

    $recurringIncome->refresh();

    expect($recurringIncome->remaining_recurrences)->toBe(5);
    expect(Income::where('user_id', $this->user->id)->count())->toBe(0);
});

test('recurring income is not deleted when remaining recurrences is one with null account', function () {
    $recurringIncome = RecurringIncome::factory()
        ->forUser($this->user)
        ->withoutAccount()
        ->create([
            'next_transaction_at' => now()->subDay(),
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => 1,
        ]);

    TransactRecurringIncomeJob::dispatch();

    expect(RecurringIncome::find($recurringIncome->id))->not->toBeNull();
    expect(Income::where('user_id', $this->user->id)->count())->toBe(0);
});
