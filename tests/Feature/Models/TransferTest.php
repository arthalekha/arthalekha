<?php

use App\Models\Account;
use App\Models\Tag;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->accountA = Account::factory()->forUser($this->user)->create(['name' => 'Account A']);
    $this->accountB = Account::factory()->forUser($this->user)->create(['name' => 'Account B']);
});

test('transfer belongs to user', function () {
    $transfer = Transfer::factory()->create([
        'user_id' => $this->user->id,
        'creditor_id' => $this->accountA->id,
        'debtor_id' => $this->accountB->id,
    ]);

    expect($transfer->user)->toBeInstanceOf(User::class);
    expect($transfer->user->id)->toBe($this->user->id);
});

test('transfer has creditor account relationship', function () {
    $transfer = Transfer::factory()->create([
        'creditor_id' => $this->accountA->id,
        'debtor_id' => $this->accountB->id,
    ]);

    expect($transfer->creditor)->toBeInstanceOf(Account::class);
    expect($transfer->creditor->id)->toBe($this->accountA->id);
    expect($transfer->creditor->name)->toBe('Account A');
});

test('transfer has debtor account relationship', function () {
    $transfer = Transfer::factory()->create([
        'creditor_id' => $this->accountA->id,
        'debtor_id' => $this->accountB->id,
    ]);

    expect($transfer->debtor)->toBeInstanceOf(Account::class);
    expect($transfer->debtor->id)->toBe($this->accountB->id);
    expect($transfer->debtor->name)->toBe('Account B');
});

test('creditor and debtor are different accounts', function () {
    $transfer = Transfer::factory()->create([
        'creditor_id' => $this->accountA->id,
        'debtor_id' => $this->accountB->id,
    ]);

    expect($transfer->creditor_id)->not->toBe($transfer->debtor_id);
    expect($transfer->creditor->id)->not->toBe($transfer->debtor->id);
});

test('transfer has MorphToMany tags relationship', function () {
    $transfer = Transfer::factory()->create([
        'creditor_id' => $this->accountA->id,
        'debtor_id' => $this->accountB->id,
    ]);
    $tags = Tag::factory()->count(3)->create();

    $transfer->tags()->attach($tags);

    expect($transfer->tags)->toHaveCount(3);
    expect($transfer->tags->first())->toBeInstanceOf(Tag::class);
});

test('tags can be attached to transfer', function () {
    $transfer = Transfer::factory()->create([
        'creditor_id' => $this->accountA->id,
        'debtor_id' => $this->accountB->id,
    ]);
    $tag = Tag::factory()->create();

    $transfer->tags()->attach($tag);

    expect($transfer->tags)->toHaveCount(1);
    expect($transfer->tags->first()->id)->toBe($tag->id);
});

test('transacted_at casts to Carbon datetime', function () {
    $transfer = Transfer::factory()->create([
        'creditor_id' => $this->accountA->id,
        'debtor_id' => $this->accountB->id,
        'transacted_at' => '2024-01-15 14:30:00',
    ]);

    expect($transfer->transacted_at)->toBeInstanceOf(\Carbon\CarbonInterface::class);
    expect($transfer->transacted_at->format('Y-m-d H:i:s'))->toBe('2024-01-15 14:30:00');
});

test('amount casts to decimal with 2 places', function () {
    $transfer = Transfer::factory()->create([
        'creditor_id' => $this->accountA->id,
        'debtor_id' => $this->accountB->id,
        'amount' => 1234.56,
    ]);

    expect($transfer->amount)->toBe('1234.56');
});

test('amount maintains decimal precision', function () {
    $transfer = Transfer::factory()->create([
        'creditor_id' => $this->accountA->id,
        'debtor_id' => $this->accountB->id,
        'amount' => '9999.99',
    ]);

    expect($transfer->amount)->toBe('9999.99');
});

test('transfer factory creates transfer with different accounts', function () {
    $transfer = Transfer::factory()->create();

    expect($transfer->creditor_id)->not->toBe($transfer->debtor_id);
    expect($transfer->creditor)->toBeInstanceOf(Account::class);
    expect($transfer->debtor)->toBeInstanceOf(Account::class);
});

test('transfer can be created with all required fields', function () {
    $transfer = Transfer::create([
        'user_id' => $this->user->id,
        'creditor_id' => $this->accountA->id,
        'debtor_id' => $this->accountB->id,
        'description' => 'Transfer between accounts',
        'transacted_at' => now(),
        'amount' => 500.00,
    ]);

    expect($transfer->user_id)->toBe($this->user->id);
    expect($transfer->creditor_id)->toBe($this->accountA->id);
    expect($transfer->debtor_id)->toBe($this->accountB->id);
    expect($transfer->description)->toBe('Transfer between accounts');
    expect($transfer->amount)->toBe('500.00');
});

test('multiple transfers can be made between same accounts', function () {
    $transfers = Transfer::factory()->count(3)->create([
        'creditor_id' => $this->accountA->id,
        'debtor_id' => $this->accountB->id,
    ]);

    expect($transfers)->toHaveCount(3);
    $transfers->each(function ($transfer) {
        expect($transfer->creditor_id)->toBe($this->accountA->id);
        expect($transfer->debtor_id)->toBe($this->accountB->id);
    });
});

test('transfer relationships can be eager loaded', function () {
    Transfer::factory()->create([
        'creditor_id' => $this->accountA->id,
        'debtor_id' => $this->accountB->id,
    ]);

    $transfer = Transfer::with(['user', 'creditor', 'debtor', 'tags'])->first();

    expect($transfer->relationLoaded('user'))->toBeTrue();
    expect($transfer->relationLoaded('creditor'))->toBeTrue();
    expect($transfer->relationLoaded('debtor'))->toBeTrue();
    expect($transfer->relationLoaded('tags'))->toBeTrue();
});

test('creditor receives money and debtor sends money', function () {
    $transfer = Transfer::factory()->create([
        'creditor_id' => $this->accountA->id,
        'debtor_id' => $this->accountB->id,
        'amount' => 100.00,
    ]);

    expect($transfer->creditor->name)->toBe('Account A');
    expect($transfer->debtor->name)->toBe('Account B');
    expect($transfer->amount)->toBe('100.00');
});
