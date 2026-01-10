<?php

use App\Models\Account;
use App\Models\Income;
use App\Models\Person;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->account = Account::factory()->forUser($this->user)->create();
});

test('income belongs to user', function () {
    $income = Income::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create();

    expect($income->user)->toBeInstanceOf(User::class);
    expect($income->user->id)->toBe($this->user->id);
});

test('income belongs to person when person_id is set', function () {
    $person = Person::factory()->create();
    $income = Income::factory()
        ->forAccount($this->account)
        ->fromPerson($person)
        ->create();

    expect($income->person)->toBeInstanceOf(Person::class);
    expect($income->person->id)->toBe($person->id);
});

test('income can have null person_id', function () {
    $income = Income::factory()
        ->forAccount($this->account)
        ->create(['person_id' => null]);

    expect($income->person_id)->toBeNull();
    expect($income->person)->toBeNull();
});

test('income belongs to account', function () {
    $income = Income::factory()->forAccount($this->account)->create();

    expect($income->account)->toBeInstanceOf(Account::class);
    expect($income->account->id)->toBe($this->account->id);
});

test('income has MorphToMany tags relationship', function () {
    $income = Income::factory()->forAccount($this->account)->create();
    $tags = Tag::factory()->count(3)->create();

    $income->tags()->attach($tags);

    expect($income->tags)->toHaveCount(3);
    expect($income->tags->first())->toBeInstanceOf(Tag::class);
});

test('tags can be attached to income', function () {
    $income = Income::factory()->forAccount($this->account)->create();
    $tag = Tag::factory()->create();

    $income->tags()->attach($tag);

    expect($income->tags)->toHaveCount(1);
    expect($income->tags->first()->id)->toBe($tag->id);
});

test('transacted_at casts to Carbon datetime', function () {
    $income = Income::factory()->forAccount($this->account)->create([
        'transacted_at' => '2024-01-15 14:30:00',
    ]);

    expect($income->transacted_at)->toBeInstanceOf(\Carbon\CarbonInterface::class);
    expect($income->transacted_at->format('Y-m-d H:i:s'))->toBe('2024-01-15 14:30:00');
});

test('amount casts to decimal with 2 places', function () {
    $income = Income::factory()->forAccount($this->account)->create([
        'amount' => 1234.56,
    ]);

    expect($income->amount)->toBe('1234.56');
});

test('amount maintains decimal precision', function () {
    $income = Income::factory()->forAccount($this->account)->create([
        'amount' => '9999.99',
    ]);

    expect($income->amount)->toBe('9999.99');
});

test('forUser factory state works', function () {
    $income = Income::factory()->forUser($this->user)->create();

    expect($income->user_id)->toBe($this->user->id);
});

test('forAccount factory state works', function () {
    $income = Income::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create();

    expect($income->account_id)->toBe($this->account->id);
    expect($income->user_id)->toBe($this->user->id);
});

test('fromPerson factory state works', function () {
    $person = Person::factory()->create();
    $income = Income::factory()
        ->forAccount($this->account)
        ->fromPerson($person)
        ->create();

    expect($income->person_id)->toBe($person->id);
    expect($income->person)->toBeInstanceOf(Person::class);
});

test('income can be created with all required fields', function () {
    $person = Person::factory()->create();

    $income = Income::create([
        'user_id' => $this->user->id,
        'account_id' => $this->account->id,
        'person_id' => $person->id,
        'description' => 'Freelance work',
        'transacted_at' => now(),
        'amount' => 500.00,
    ]);

    expect($income->user_id)->toBe($this->user->id);
    expect($income->account_id)->toBe($this->account->id);
    expect($income->person_id)->toBe($person->id);
    expect($income->description)->toBe('Freelance work');
    expect($income->amount)->toBe('500.00');
});

test('multiple incomes can belong to same account', function () {
    $incomes = Income::factory()->count(3)->forAccount($this->account)->create();

    expect($incomes)->toHaveCount(3);
    $incomes->each(fn ($income) => expect($income->account_id)->toBe($this->account->id));
});

test('income relationships can be eager loaded', function () {
    Income::factory()
        ->forAccount($this->account)
        ->fromPerson(Person::factory()->create())
        ->create();

    $income = Income::with(['user', 'account', 'person', 'tags'])->first();

    expect($income->relationLoaded('user'))->toBeTrue();
    expect($income->relationLoaded('account'))->toBeTrue();
    expect($income->relationLoaded('person'))->toBeTrue();
    expect($income->relationLoaded('tags'))->toBeTrue();
});
