<?php

use App\Models\Account;
use App\Models\Expense;
use App\Models\Person;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->account = Account::factory()->forUser($this->user)->create();
});

test('expense belongs to user', function () {
    $expense = Expense::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create();

    expect($expense->user)->toBeInstanceOf(User::class);
    expect($expense->user->id)->toBe($this->user->id);
});

test('expense belongs to person when person_id is set', function () {
    $person = Person::factory()->create();
    $expense = Expense::factory()
        ->forAccount($this->account)
        ->toPerson($person)
        ->create();

    expect($expense->person)->toBeInstanceOf(Person::class);
    expect($expense->person->id)->toBe($person->id);
});

test('expense can have null person_id', function () {
    $expense = Expense::factory()
        ->forAccount($this->account)
        ->create(['person_id' => null]);

    expect($expense->person_id)->toBeNull();
    expect($expense->person)->toBeNull();
});

test('expense belongs to account', function () {
    $expense = Expense::factory()->forAccount($this->account)->create();

    expect($expense->account)->toBeInstanceOf(Account::class);
    expect($expense->account->id)->toBe($this->account->id);
});

test('expense has MorphToMany tags relationship', function () {
    $expense = Expense::factory()->forAccount($this->account)->create();
    $tags = Tag::factory()->count(3)->create();

    $expense->tags()->attach($tags);

    expect($expense->tags)->toHaveCount(3);
    expect($expense->tags->first())->toBeInstanceOf(Tag::class);
});

test('tags can be attached to expense', function () {
    $expense = Expense::factory()->forAccount($this->account)->create();
    $tag = Tag::factory()->create();

    $expense->tags()->attach($tag);

    expect($expense->tags)->toHaveCount(1);
    expect($expense->tags->first()->id)->toBe($tag->id);
});

test('tags can be synced on expense', function () {
    $expense = Expense::factory()->forAccount($this->account)->create();

    $initialTags = Tag::factory()->count(2)->create();
    $expense->tags()->attach($initialTags);
    expect($expense->tags)->toHaveCount(2);

    $newTags = Tag::factory()->count(3)->create();
    $expense->tags()->sync($newTags->pluck('id'));
    $expense->refresh();

    expect($expense->tags)->toHaveCount(3);
});

test('tags can be detached from expense', function () {
    $expense = Expense::factory()->forAccount($this->account)->create();
    $tags = Tag::factory()->count(3)->create();

    $expense->tags()->attach($tags);
    expect($expense->tags)->toHaveCount(3);

    $expense->tags()->detach($tags->first());
    $expense->refresh();

    expect($expense->tags)->toHaveCount(2);
});

test('transacted_at casts to Carbon datetime', function () {
    $expense = Expense::factory()->forAccount($this->account)->create([
        'transacted_at' => '2024-01-15 14:30:00',
    ]);

    expect($expense->transacted_at)->toBeInstanceOf(\Carbon\CarbonInterface::class);
    expect($expense->transacted_at->format('Y-m-d H:i:s'))->toBe('2024-01-15 14:30:00');
});

test('amount casts to decimal with 2 places', function () {
    $expense = Expense::factory()->forAccount($this->account)->create([
        'amount' => 1234.56,
    ]);

    expect($expense->amount)->toBe('1234.56');
});

test('amount maintains decimal precision', function () {
    $expense = Expense::factory()->forAccount($this->account)->create([
        'amount' => '9999.99',
    ]);

    expect($expense->amount)->toBe('9999.99');
});

test('forUser factory state works', function () {
    $expense = Expense::factory()->forUser($this->user)->create();

    expect($expense->user_id)->toBe($this->user->id);
});

test('forAccount factory state works', function () {
    $expense = Expense::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create();

    expect($expense->account_id)->toBe($this->account->id);
    expect($expense->user_id)->toBe($this->user->id);
});

test('toPerson factory state works', function () {
    $person = Person::factory()->create();
    $expense = Expense::factory()
        ->forAccount($this->account)
        ->toPerson($person)
        ->create();

    expect($expense->person_id)->toBe($person->id);
    expect($expense->person)->toBeInstanceOf(Person::class);
});

test('expense can be created with all required fields', function () {
    $person = Person::factory()->create();

    $expense = Expense::create([
        'user_id' => $this->user->id,
        'account_id' => $this->account->id,
        'person_id' => $person->id,
        'description' => 'Office supplies',
        'transacted_at' => now(),
        'amount' => 250.00,
    ]);

    expect($expense->user_id)->toBe($this->user->id);
    expect($expense->account_id)->toBe($this->account->id);
    expect($expense->person_id)->toBe($person->id);
    expect($expense->description)->toBe('Office supplies');
    expect($expense->amount)->toBe('250.00');
});

test('multiple expenses can belong to same account', function () {
    $expenses = Expense::factory()->count(3)->forAccount($this->account)->create();

    expect($expenses)->toHaveCount(3);
    $expenses->each(fn ($expense) => expect($expense->account_id)->toBe($this->account->id));
});

test('expense relationships can be eager loaded', function () {
    Expense::factory()
        ->forAccount($this->account)
        ->toPerson(Person::factory()->create())
        ->create();

    $expense = Expense::with(['user', 'account', 'person', 'tags'])->first();

    expect($expense->relationLoaded('user'))->toBeTrue();
    expect($expense->relationLoaded('account'))->toBeTrue();
    expect($expense->relationLoaded('person'))->toBeTrue();
    expect($expense->relationLoaded('tags'))->toBeTrue();
});
