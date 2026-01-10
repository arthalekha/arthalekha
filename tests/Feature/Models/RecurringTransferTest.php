<?php

use App\Enums\Frequency;
use App\Models\Account;
use App\Models\RecurringTransfer;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->accountA = Account::factory()->forUser($this->user)->create(['name' => 'Account A']);
    $this->accountB = Account::factory()->forUser($this->user)->create(['name' => 'Account B']);
});

test('recurring transfer belongs to user', function () {
    $recurringTransfer = RecurringTransfer::factory()->create([
        'user_id' => $this->user->id,
        'creditor_id' => $this->accountA->id,
        'debtor_id' => $this->accountB->id,
    ]);

    expect($recurringTransfer->user)->toBeInstanceOf(User::class);
    expect($recurringTransfer->user->id)->toBe($this->user->id);
});

test('recurring transfer has creditor account relationship', function () {
    $recurringTransfer = RecurringTransfer::factory()->create([
        'creditor_id' => $this->accountA->id,
        'debtor_id' => $this->accountB->id,
    ]);

    expect($recurringTransfer->creditor)->toBeInstanceOf(Account::class);
    expect($recurringTransfer->creditor->id)->toBe($this->accountA->id);
    expect($recurringTransfer->creditor->name)->toBe('Account A');
});

test('recurring transfer has debtor account relationship', function () {
    $recurringTransfer = RecurringTransfer::factory()->create([
        'creditor_id' => $this->accountA->id,
        'debtor_id' => $this->accountB->id,
    ]);

    expect($recurringTransfer->debtor)->toBeInstanceOf(Account::class);
    expect($recurringTransfer->debtor->id)->toBe($this->accountB->id);
    expect($recurringTransfer->debtor->name)->toBe('Account B');
});

test('creditor and debtor are different accounts', function () {
    $recurringTransfer = RecurringTransfer::factory()->create([
        'creditor_id' => $this->accountA->id,
        'debtor_id' => $this->accountB->id,
    ]);

    expect($recurringTransfer->creditor_id)->not->toBe($recurringTransfer->debtor_id);
    expect($recurringTransfer->creditor->id)->not->toBe($recurringTransfer->debtor->id);
});

test('recurring transfer has MorphToMany tags relationship', function () {
    $recurringTransfer = RecurringTransfer::factory()->create([
        'creditor_id' => $this->accountA->id,
        'debtor_id' => $this->accountB->id,
    ]);
    $tags = Tag::factory()->count(3)->create();

    $recurringTransfer->tags()->attach($tags);

    expect($recurringTransfer->tags)->toHaveCount(3);
    expect($recurringTransfer->tags->first())->toBeInstanceOf(Tag::class);
});

test('next_transaction_at casts to Carbon datetime', function () {
    $recurringTransfer = RecurringTransfer::factory()->create([
        'creditor_id' => $this->accountA->id,
        'debtor_id' => $this->accountB->id,
        'next_transaction_at' => '2024-02-01 10:00:00',
    ]);

    expect($recurringTransfer->next_transaction_at)->toBeInstanceOf(\Carbon\CarbonInterface::class);
    expect($recurringTransfer->next_transaction_at->format('Y-m-d H:i:s'))->toBe('2024-02-01 10:00:00');
});

test('amount casts to decimal with 2 places', function () {
    $recurringTransfer = RecurringTransfer::factory()->create([
        'creditor_id' => $this->accountA->id,
        'debtor_id' => $this->accountB->id,
        'amount' => 1234.56,
    ]);

    expect($recurringTransfer->amount)->toBe('1234.56');
});

test('frequency casts to Frequency enum', function () {
    $recurringTransfer = RecurringTransfer::factory()->create([
        'creditor_id' => $this->accountA->id,
        'debtor_id' => $this->accountB->id,
        'frequency' => Frequency::Monthly,
    ]);

    expect($recurringTransfer->frequency)->toBeInstanceOf(Frequency::class);
    expect($recurringTransfer->frequency)->toBe(Frequency::Monthly);
});

test('remaining_recurrences casts to integer', function () {
    $recurringTransfer = RecurringTransfer::factory()->create([
        'creditor_id' => $this->accountA->id,
        'debtor_id' => $this->accountB->id,
        'remaining_recurrences' => 12,
    ]);

    expect($recurringTransfer->remaining_recurrences)->toBeInt();
    expect($recurringTransfer->remaining_recurrences)->toBe(12);
});

test('remaining_recurrences can be null for infinite recurrences', function () {
    $recurringTransfer = RecurringTransfer::factory()->create([
        'creditor_id' => $this->accountA->id,
        'debtor_id' => $this->accountB->id,
        'remaining_recurrences' => null,
    ]);

    expect($recurringTransfer->remaining_recurrences)->toBeNull();
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
        $recurringTransfer = RecurringTransfer::factory()->create([
            'creditor_id' => $this->accountA->id,
            'debtor_id' => $this->accountB->id,
            'frequency' => $frequency,
        ]);

        expect($recurringTransfer->frequency)->toBe($frequency);
    }
});

test('recurring transfer can be created with all required fields', function () {
    $recurringTransfer = RecurringTransfer::create([
        'user_id' => $this->user->id,
        'creditor_id' => $this->accountA->id,
        'debtor_id' => $this->accountB->id,
        'description' => 'Monthly savings transfer',
        'amount' => 500.00,
        'next_transaction_at' => now()->addMonth(),
        'frequency' => Frequency::Monthly,
        'remaining_recurrences' => 12,
    ]);

    expect($recurringTransfer->user_id)->toBe($this->user->id);
    expect($recurringTransfer->creditor_id)->toBe($this->accountA->id);
    expect($recurringTransfer->debtor_id)->toBe($this->accountB->id);
    expect($recurringTransfer->description)->toBe('Monthly savings transfer');
    expect($recurringTransfer->amount)->toBe('500.00');
    expect($recurringTransfer->frequency)->toBe(Frequency::Monthly);
    expect($recurringTransfer->remaining_recurrences)->toBe(12);
});

test('recurring transfer relationships can be eager loaded', function () {
    RecurringTransfer::factory()->create([
        'creditor_id' => $this->accountA->id,
        'debtor_id' => $this->accountB->id,
    ]);

    $recurringTransfer = RecurringTransfer::with(['user', 'creditor', 'debtor', 'tags'])->first();

    expect($recurringTransfer->relationLoaded('user'))->toBeTrue();
    expect($recurringTransfer->relationLoaded('creditor'))->toBeTrue();
    expect($recurringTransfer->relationLoaded('debtor'))->toBeTrue();
    expect($recurringTransfer->relationLoaded('tags'))->toBeTrue();
});

test('frequency enum values are stored correctly', function () {
    $recurringTransfer = RecurringTransfer::factory()->create([
        'creditor_id' => $this->accountA->id,
        'debtor_id' => $this->accountB->id,
        'frequency' => Frequency::Biweekly,
    ]);

    $recurringTransfer->refresh();

    expect($recurringTransfer->frequency)->toBe(Frequency::Biweekly);
    expect($recurringTransfer->frequency->value)->toBe('biweekly');
});

test('recurring transfer factory creates transfer with different accounts', function () {
    $recurringTransfer = RecurringTransfer::factory()->create();

    expect($recurringTransfer->creditor_id)->not->toBe($recurringTransfer->debtor_id);
    expect($recurringTransfer->creditor)->toBeInstanceOf(Account::class);
    expect($recurringTransfer->debtor)->toBeInstanceOf(Account::class);
});
