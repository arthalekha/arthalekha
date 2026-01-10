<?php

use App\Enums\Frequency;
use App\Models\Account;
use App\Models\Person;
use App\Models\RecurringExpense;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->account = Account::factory()->forUser($this->user)->create();
});

test('recurring expense belongs to user', function () {
    $recurringExpense = RecurringExpense::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create();

    expect($recurringExpense->user)->toBeInstanceOf(User::class);
    expect($recurringExpense->user->id)->toBe($this->user->id);
});

test('recurring expense belongs to person when person_id is set', function () {
    $person = Person::factory()->create();
    $recurringExpense = RecurringExpense::factory()
        ->forAccount($this->account)
        ->create(['person_id' => $person->id]);

    expect($recurringExpense->person)->toBeInstanceOf(Person::class);
    expect($recurringExpense->person->id)->toBe($person->id);
});

test('recurring expense can have null person_id', function () {
    $recurringExpense = RecurringExpense::factory()
        ->forAccount($this->account)
        ->create(['person_id' => null]);

    expect($recurringExpense->person_id)->toBeNull();
    expect($recurringExpense->person)->toBeNull();
});

test('recurring expense belongs to account', function () {
    $recurringExpense = RecurringExpense::factory()
        ->forAccount($this->account)
        ->create();

    expect($recurringExpense->account)->toBeInstanceOf(Account::class);
    expect($recurringExpense->account->id)->toBe($this->account->id);
});

test('recurring expense has MorphToMany tags relationship', function () {
    $recurringExpense = RecurringExpense::factory()
        ->forAccount($this->account)
        ->create();
    $tags = Tag::factory()->count(3)->create();

    $recurringExpense->tags()->attach($tags);

    expect($recurringExpense->tags)->toHaveCount(3);
    expect($recurringExpense->tags->first())->toBeInstanceOf(Tag::class);
});

test('next_transaction_at casts to Carbon datetime', function () {
    $recurringExpense = RecurringExpense::factory()
        ->forAccount($this->account)
        ->create([
            'next_transaction_at' => '2024-02-01 10:00:00',
        ]);

    expect($recurringExpense->next_transaction_at)->toBeInstanceOf(\Carbon\CarbonInterface::class);
    expect($recurringExpense->next_transaction_at->format('Y-m-d H:i:s'))->toBe('2024-02-01 10:00:00');
});

test('amount casts to decimal with 2 places', function () {
    $recurringExpense = RecurringExpense::factory()
        ->forAccount($this->account)
        ->create(['amount' => 1234.56]);

    expect($recurringExpense->amount)->toBe('1234.56');
});

test('frequency casts to Frequency enum', function () {
    $recurringExpense = RecurringExpense::factory()
        ->forAccount($this->account)
        ->create(['frequency' => Frequency::Monthly]);

    expect($recurringExpense->frequency)->toBeInstanceOf(Frequency::class);
    expect($recurringExpense->frequency)->toBe(Frequency::Monthly);
});

test('remaining_recurrences casts to integer', function () {
    $recurringExpense = RecurringExpense::factory()
        ->forAccount($this->account)
        ->create(['remaining_recurrences' => 12]);

    expect($recurringExpense->remaining_recurrences)->toBeInt();
    expect($recurringExpense->remaining_recurrences)->toBe(12);
});

test('remaining_recurrences can be null for infinite recurrences', function () {
    $recurringExpense = RecurringExpense::factory()
        ->forAccount($this->account)
        ->create(['remaining_recurrences' => null]);

    expect($recurringExpense->remaining_recurrences)->toBeNull();
});

test('different Frequency enum values work correctly', function () {
    $frequencies = [
        Frequency::Daily,
        Frequency::Weekly,
        Frequency::Biweekly,
        Frequency::Monthly,
        Frequency::Quarterly,
        Frequency::Yearly,
    ];

    foreach ($frequencies as $frequency) {
        $recurringExpense = RecurringExpense::factory()
            ->forAccount($this->account)
            ->create(['frequency' => $frequency]);

        expect($recurringExpense->frequency)->toBe($frequency);
    }
});

test('recurring expense can be created with all required fields', function () {
    $person = Person::factory()->create();

    $recurringExpense = RecurringExpense::create([
        'user_id' => $this->user->id,
        'account_id' => $this->account->id,
        'person_id' => $person->id,
        'description' => 'Monthly rent',
        'amount' => 1500.00,
        'next_transaction_at' => now()->addMonth(),
        'frequency' => Frequency::Monthly,
        'remaining_recurrences' => 12,
    ]);

    expect($recurringExpense->user_id)->toBe($this->user->id);
    expect($recurringExpense->account_id)->toBe($this->account->id);
    expect($recurringExpense->person_id)->toBe($person->id);
    expect($recurringExpense->description)->toBe('Monthly rent');
    expect($recurringExpense->amount)->toBe('1500.00');
    expect($recurringExpense->frequency)->toBe(Frequency::Monthly);
    expect($recurringExpense->remaining_recurrences)->toBe(12);
});

test('recurring expense relationships can be eager loaded', function () {
    RecurringExpense::factory()
        ->forAccount($this->account)
        ->create(['person_id' => Person::factory()->create()->id]);

    $recurringExpense = RecurringExpense::with(['user', 'account', 'person', 'tags'])->first();

    expect($recurringExpense->relationLoaded('user'))->toBeTrue();
    expect($recurringExpense->relationLoaded('account'))->toBeTrue();
    expect($recurringExpense->relationLoaded('person'))->toBeTrue();
    expect($recurringExpense->relationLoaded('tags'))->toBeTrue();
});

test('frequency enum values are stored correctly', function () {
    $recurringExpense = RecurringExpense::factory()
        ->forAccount($this->account)
        ->create(['frequency' => Frequency::Weekly]);

    $recurringExpense->refresh();

    expect($recurringExpense->frequency)->toBe(Frequency::Weekly);
    expect($recurringExpense->frequency->value)->toBe('weekly');
});
