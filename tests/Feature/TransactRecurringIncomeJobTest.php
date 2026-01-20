<?php

use App\Enums\Frequency;
use App\Jobs\TransactRecurringIncomeJob;
use App\Models\Account;
use App\Models\Income;
use App\Models\RecurringIncome;
use App\Models\User;
use Database\Factories\TagFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->account = Account::factory()->forUser($this->user)->create();
});

test('it creates income from due recurring income', function () {
    $recurringIncome = RecurringIncome::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create([
            'next_transaction_at' => now()->subDay(),
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => null,
        ]);

    (new TransactRecurringIncomeJob)->handle();

    expect(Income::count())->toBe(1);

    $income = Income::first();
    expect($income->user_id)->toBe($recurringIncome->user_id)
        ->and($income->account_id)->toBe($recurringIncome->account_id)
        ->and($income->description)->toBe($recurringIncome->description)
        ->and((float) $income->amount)->toBe((float) $recurringIncome->amount);
});

test('it does not create income for future recurring incomes', function () {
    RecurringIncome::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create([
            'next_transaction_at' => now()->addDay(),
            'frequency' => Frequency::Monthly,
        ]);

    (new TransactRecurringIncomeJob)->handle();

    expect(Income::count())->toBe(0);
});

test('it updates next_transaction_at based on frequency', function (Frequency $frequency, string $expectedDate) {
    $baseDate = now()->startOfDay();

    $recurringIncome = RecurringIncome::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create([
            'next_transaction_at' => $baseDate,
            'frequency' => $frequency,
            'remaining_recurrences' => null,
        ]);

    (new TransactRecurringIncomeJob)->handle();

    $recurringIncome->refresh();
    expect($recurringIncome->next_transaction_at->toDateString())->toBe($expectedDate);
})->with([
    'daily' => [Frequency::Daily, now()->startOfDay()->addDay()->toDateString()],
    'weekly' => [Frequency::Weekly, now()->startOfDay()->addWeek()->toDateString()],
    'biweekly' => [Frequency::Biweekly, now()->startOfDay()->addWeeks(2)->toDateString()],
    'monthly' => [Frequency::Monthly, now()->startOfDay()->addMonth()->toDateString()],
    'quarterly' => [Frequency::Quarterly, now()->startOfDay()->addMonths(3)->toDateString()],
    'yearly' => [Frequency::Yearly, now()->startOfDay()->addYear()->toDateString()],
]);

test('it decrements remaining recurrences', function () {
    $recurringIncome = RecurringIncome::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create([
            'next_transaction_at' => now()->subDay(),
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => 5,
        ]);

    (new TransactRecurringIncomeJob)->handle();

    $recurringIncome->refresh();
    expect($recurringIncome->remaining_recurrences)->toBe(4);
});

test('it deletes recurring income when remaining recurrences reaches zero', function () {
    $recurringIncome = RecurringIncome::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create([
            'next_transaction_at' => now()->subDay(),
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => 1,
        ]);

    (new TransactRecurringIncomeJob)->handle();

    expect(Income::count())->toBe(1);
    expect(RecurringIncome::find($recurringIncome->id))->toBeNull();
});

test('it does not delete recurring income with unlimited recurrences', function () {
    $recurringIncome = RecurringIncome::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->unlimited()
        ->create([
            'next_transaction_at' => now()->subDay(),
            'frequency' => Frequency::Monthly,
        ]);

    (new TransactRecurringIncomeJob)->handle();

    expect(Income::count())->toBe(1);
    expect(RecurringIncome::find($recurringIncome->id))->not->toBeNull();
});

test('it copies tags from recurring income to income', function () {
    $tags = TagFactory::new()->count(3)->create();

    $recurringIncome = RecurringIncome::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create([
            'next_transaction_at' => now()->subDay(),
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => null,
        ]);

    $recurringIncome->tags()->sync($tags->pluck('id'));

    (new TransactRecurringIncomeJob)->handle();

    $income = Income::first();
    expect($income->tags)->toHaveCount(3);
    expect($income->tags->pluck('id')->toArray())->toBe($tags->pluck('id')->toArray());
});

test('it processes multiple due recurring incomes', function () {
    RecurringIncome::factory()
        ->count(3)
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create([
            'next_transaction_at' => now()->subDay(),
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => null,
        ]);

    (new TransactRecurringIncomeJob)->handle();

    expect(Income::count())->toBe(3);
});

test('it sets transacted_at to the scheduled date', function () {
    $scheduledDate = now()->subDays(3);

    $recurringIncome = RecurringIncome::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create([
            'next_transaction_at' => $scheduledDate,
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => null,
        ]);

    (new TransactRecurringIncomeJob)->handle();

    $income = Income::first();
    expect($income->transacted_at->toDateString())->toBe($scheduledDate->toDateString());
});

test('it copies person_id from recurring income to income', function () {
    $recurringIncome = RecurringIncome::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create([
            'next_transaction_at' => now()->subDay(),
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => null,
        ]);

    (new TransactRecurringIncomeJob)->handle();

    $income = Income::first();
    expect($income->person_id)->toBe($recurringIncome->person_id);
});
