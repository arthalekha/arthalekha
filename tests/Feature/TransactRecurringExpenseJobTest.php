<?php

use App\Enums\Frequency;
use App\Jobs\TransactRecurringExpenseJob;
use App\Models\Account;
use App\Models\Expense;
use App\Models\RecurringExpense;
use App\Models\User;
use Database\Factories\TagFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->account = Account::factory()->forUser($this->user)->create();
});

test('it creates expense from due recurring expense', function () {
    $recurringExpense = RecurringExpense::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create([
            'next_transaction_at' => now()->subDay(),
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => null,
        ]);

    (new TransactRecurringExpenseJob)->handle();

    expect(Expense::count())->toBe(1);

    $expense = Expense::first();
    expect($expense->user_id)->toBe($recurringExpense->user_id)
        ->and($expense->account_id)->toBe($recurringExpense->account_id)
        ->and($expense->description)->toBe($recurringExpense->description)
        ->and((float) $expense->amount)->toBe((float) $recurringExpense->amount);
});

test('it does not create expense for future recurring expenses', function () {
    RecurringExpense::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create([
            'next_transaction_at' => now()->addDay(),
            'frequency' => Frequency::Monthly,
        ]);

    (new TransactRecurringExpenseJob)->handle();

    expect(Expense::count())->toBe(0);
});

test('it updates next_transaction_at based on frequency', function (Frequency $frequency, string $expectedDate) {
    $baseDate = now()->startOfDay();

    $recurringExpense = RecurringExpense::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create([
            'next_transaction_at' => $baseDate,
            'frequency' => $frequency,
            'remaining_recurrences' => null,
        ]);

    (new TransactRecurringExpenseJob)->handle();

    $recurringExpense->refresh();
    expect($recurringExpense->next_transaction_at->toDateString())->toBe($expectedDate);
})->with([
    'daily' => [Frequency::Daily, now()->startOfDay()->addDay()->toDateString()],
    'weekly' => [Frequency::Weekly, now()->startOfDay()->addWeek()->toDateString()],
    'biweekly' => [Frequency::Biweekly, now()->startOfDay()->addWeeks(2)->toDateString()],
    'monthly' => [Frequency::Monthly, now()->startOfDay()->addMonth()->toDateString()],
    'quarterly' => [Frequency::Quarterly, now()->startOfDay()->addMonths(3)->toDateString()],
    'yearly' => [Frequency::Yearly, now()->startOfDay()->addYear()->toDateString()],
]);

test('it decrements remaining recurrences', function () {
    $recurringExpense = RecurringExpense::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create([
            'next_transaction_at' => now()->subDay(),
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => 5,
        ]);

    (new TransactRecurringExpenseJob)->handle();

    $recurringExpense->refresh();
    expect($recurringExpense->remaining_recurrences)->toBe(4);
});

test('it deletes recurring expense when remaining recurrences reaches zero', function () {
    $recurringExpense = RecurringExpense::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create([
            'next_transaction_at' => now()->subDay(),
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => 1,
        ]);

    (new TransactRecurringExpenseJob)->handle();

    expect(Expense::count())->toBe(1);
    expect(RecurringExpense::find($recurringExpense->id))->toBeNull();
});

test('it does not delete recurring expense with unlimited recurrences', function () {
    $recurringExpense = RecurringExpense::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->unlimited()
        ->create([
            'next_transaction_at' => now()->subDay(),
            'frequency' => Frequency::Monthly,
        ]);

    (new TransactRecurringExpenseJob)->handle();

    expect(Expense::count())->toBe(1);
    expect(RecurringExpense::find($recurringExpense->id))->not->toBeNull();
});

test('it copies tags from recurring expense to expense', function () {
    $tags = TagFactory::new()->count(3)->create();

    $recurringExpense = RecurringExpense::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create([
            'next_transaction_at' => now()->subDay(),
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => null,
        ]);

    $recurringExpense->tags()->sync($tags->pluck('id'));

    (new TransactRecurringExpenseJob)->handle();

    $expense = Expense::first();
    expect($expense->tags)->toHaveCount(3);
    expect($expense->tags->pluck('id')->toArray())->toBe($tags->pluck('id')->toArray());
});

test('it processes multiple due recurring expenses', function () {
    RecurringExpense::factory()
        ->count(3)
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create([
            'next_transaction_at' => now()->subDay(),
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => null,
        ]);

    (new TransactRecurringExpenseJob)->handle();

    expect(Expense::count())->toBe(3);
});

test('it sets transacted_at to the scheduled date', function () {
    $scheduledDate = now()->subDays(3);

    $recurringExpense = RecurringExpense::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create([
            'next_transaction_at' => $scheduledDate,
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => null,
        ]);

    (new TransactRecurringExpenseJob)->handle();

    $expense = Expense::first();
    expect($expense->transacted_at->toDateString())->toBe($scheduledDate->toDateString());
});

test('it copies person_id from recurring expense to expense', function () {
    $recurringExpense = RecurringExpense::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create([
            'next_transaction_at' => now()->subDay(),
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => null,
        ]);

    (new TransactRecurringExpenseJob)->handle();

    $expense = Expense::first();
    expect($expense->person_id)->toBe($recurringExpense->person_id);
});
