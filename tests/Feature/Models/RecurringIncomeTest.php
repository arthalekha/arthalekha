<?php

use App\Enums\Frequency;
use App\Models\Account;
use App\Models\Person;
use App\Models\RecurringIncome;
use App\Models\User;
use Database\Factories\TagFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use SourcedOpen\Tags\Models\Tag;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->account = Account::factory()->forUser($this->user)->create();
});

test('recurring income belongs to user', function () {
    $recurringIncome = RecurringIncome::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create();

    expect($recurringIncome->user)->toBeInstanceOf(User::class);
    expect($recurringIncome->user->id)->toBe($this->user->id);
});

test('recurring income belongs to person when person_id is set', function () {
    $person = Person::factory()->create();
    $recurringIncome = RecurringIncome::factory()
        ->forAccount($this->account)
        ->create(['person_id' => $person->id]);

    expect($recurringIncome->person)->toBeInstanceOf(Person::class);
    expect($recurringIncome->person->id)->toBe($person->id);
});

test('recurring income can have null person_id', function () {
    $recurringIncome = RecurringIncome::factory()
        ->forAccount($this->account)
        ->create(['person_id' => null]);

    expect($recurringIncome->person_id)->toBeNull();
    expect($recurringIncome->person)->toBeNull();
});

test('recurring income belongs to account', function () {
    $recurringIncome = RecurringIncome::factory()
        ->forAccount($this->account)
        ->create();

    expect($recurringIncome->account)->toBeInstanceOf(Account::class);
    expect($recurringIncome->account->id)->toBe($this->account->id);
});

test('recurring income has MorphToMany tags relationship', function () {
    $recurringIncome = RecurringIncome::factory()
        ->forAccount($this->account)
        ->create();
    $tags = TagFactory::new()->count(3)->create();

    $recurringIncome->tags()->attach($tags);

    expect($recurringIncome->tags)->toHaveCount(3);
    expect($recurringIncome->tags->first())->toBeInstanceOf(Tag::class);
});

test('next_transaction_at casts to Carbon datetime', function () {
    $recurringIncome = RecurringIncome::factory()
        ->forAccount($this->account)
        ->create([
            'next_transaction_at' => '2024-02-01 10:00:00',
        ]);

    expect($recurringIncome->next_transaction_at)->toBeInstanceOf(\Carbon\CarbonInterface::class);
    expect($recurringIncome->next_transaction_at->format('Y-m-d H:i:s'))->toBe('2024-02-01 10:00:00');
});

test('amount casts to decimal with 2 places', function () {
    $recurringIncome = RecurringIncome::factory()
        ->forAccount($this->account)
        ->create(['amount' => 1234.56]);

    expect($recurringIncome->amount)->toBe('1234.56');
});

test('frequency casts to Frequency enum', function () {
    $recurringIncome = RecurringIncome::factory()
        ->forAccount($this->account)
        ->create(['frequency' => Frequency::Monthly]);

    expect($recurringIncome->frequency)->toBeInstanceOf(Frequency::class);
    expect($recurringIncome->frequency)->toBe(Frequency::Monthly);
});

test('remaining_recurrences casts to integer', function () {
    $recurringIncome = RecurringIncome::factory()
        ->forAccount($this->account)
        ->create(['remaining_recurrences' => 12]);

    expect($recurringIncome->remaining_recurrences)->toBeInt();
    expect($recurringIncome->remaining_recurrences)->toBe(12);
});

test('remaining_recurrences can be null for infinite recurrences', function () {
    $recurringIncome = RecurringIncome::factory()
        ->forAccount($this->account)
        ->create(['remaining_recurrences' => null]);

    expect($recurringIncome->remaining_recurrences)->toBeNull();
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
        $recurringIncome = RecurringIncome::factory()
            ->forAccount($this->account)
            ->create(['frequency' => $frequency]);

        expect($recurringIncome->frequency)->toBe($frequency);
    }
});

test('recurring income can be created with all required fields', function () {
    $person = Person::factory()->create();

    $recurringIncome = RecurringIncome::create([
        'user_id' => $this->user->id,
        'account_id' => $this->account->id,
        'person_id' => $person->id,
        'description' => 'Monthly salary',
        'amount' => 5000.00,
        'next_transaction_at' => now()->addMonth(),
        'frequency' => Frequency::Monthly,
        'remaining_recurrences' => 12,
    ]);

    expect($recurringIncome->user_id)->toBe($this->user->id);
    expect($recurringIncome->account_id)->toBe($this->account->id);
    expect($recurringIncome->person_id)->toBe($person->id);
    expect($recurringIncome->description)->toBe('Monthly salary');
    expect($recurringIncome->amount)->toBe('5000.00');
    expect($recurringIncome->frequency)->toBe(Frequency::Monthly);
    expect($recurringIncome->remaining_recurrences)->toBe(12);
});

test('recurring income relationships can be eager loaded', function () {
    RecurringIncome::factory()
        ->forAccount($this->account)
        ->create(['person_id' => Person::factory()->create()->id]);

    $recurringIncome = RecurringIncome::with(['user', 'account', 'person', 'tags'])->first();

    expect($recurringIncome->relationLoaded('user'))->toBeTrue();
    expect($recurringIncome->relationLoaded('account'))->toBeTrue();
    expect($recurringIncome->relationLoaded('person'))->toBeTrue();
    expect($recurringIncome->relationLoaded('tags'))->toBeTrue();
});

test('frequency enum values are stored correctly', function () {
    $recurringIncome = RecurringIncome::factory()
        ->forAccount($this->account)
        ->create(['frequency' => Frequency::Quarterly]);

    $recurringIncome->refresh();

    expect($recurringIncome->frequency)->toBe(Frequency::Quarterly);
    expect($recurringIncome->frequency->value)->toBe('quarterly');
});
