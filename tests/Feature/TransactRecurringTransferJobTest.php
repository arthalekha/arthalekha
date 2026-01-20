<?php

use App\Enums\Frequency;
use App\Jobs\TransactRecurringTransferJob;
use App\Models\Account;
use App\Models\RecurringTransfer;
use App\Models\Transfer;
use App\Models\User;
use Database\Factories\TagFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->creditorAccount = Account::factory()->forUser($this->user)->create();
    $this->debtorAccount = Account::factory()->forUser($this->user)->create();
});

test('it creates transfer from due recurring transfer', function () {
    $recurringTransfer = RecurringTransfer::factory()
        ->forUser($this->user)
        ->toAccount($this->creditorAccount)
        ->fromAccount($this->debtorAccount)
        ->create([
            'next_transaction_at' => now()->subDay(),
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => null,
        ]);

    (new TransactRecurringTransferJob)->handle();

    expect(Transfer::count())->toBe(1);

    $transfer = Transfer::first();
    expect($transfer->user_id)->toBe($recurringTransfer->user_id)
        ->and($transfer->creditor_id)->toBe($recurringTransfer->creditor_id)
        ->and($transfer->debtor_id)->toBe($recurringTransfer->debtor_id)
        ->and($transfer->description)->toBe($recurringTransfer->description)
        ->and((float) $transfer->amount)->toBe((float) $recurringTransfer->amount);
});

test('it does not create transfer for future recurring transfers', function () {
    RecurringTransfer::factory()
        ->forUser($this->user)
        ->toAccount($this->creditorAccount)
        ->fromAccount($this->debtorAccount)
        ->create([
            'next_transaction_at' => now()->addDay(),
            'frequency' => Frequency::Monthly,
        ]);

    (new TransactRecurringTransferJob)->handle();

    expect(Transfer::count())->toBe(0);
});

test('it updates next_transaction_at based on frequency', function (Frequency $frequency, string $expectedDate) {
    $baseDate = now()->startOfDay();

    $recurringTransfer = RecurringTransfer::factory()
        ->forUser($this->user)
        ->toAccount($this->creditorAccount)
        ->fromAccount($this->debtorAccount)
        ->create([
            'next_transaction_at' => $baseDate,
            'frequency' => $frequency,
            'remaining_recurrences' => null,
        ]);

    (new TransactRecurringTransferJob)->handle();

    $recurringTransfer->refresh();
    expect($recurringTransfer->next_transaction_at->toDateString())->toBe($expectedDate);
})->with([
    'daily' => [Frequency::Daily, now()->startOfDay()->addDay()->toDateString()],
    'weekly' => [Frequency::Weekly, now()->startOfDay()->addWeek()->toDateString()],
    'biweekly' => [Frequency::Biweekly, now()->startOfDay()->addWeeks(2)->toDateString()],
    'monthly' => [Frequency::Monthly, now()->startOfDay()->addMonth()->toDateString()],
    'quarterly' => [Frequency::Quarterly, now()->startOfDay()->addMonths(3)->toDateString()],
    'yearly' => [Frequency::Yearly, now()->startOfDay()->addYear()->toDateString()],
]);

test('it decrements remaining recurrences', function () {
    $recurringTransfer = RecurringTransfer::factory()
        ->forUser($this->user)
        ->toAccount($this->creditorAccount)
        ->fromAccount($this->debtorAccount)
        ->create([
            'next_transaction_at' => now()->subDay(),
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => 5,
        ]);

    (new TransactRecurringTransferJob)->handle();

    $recurringTransfer->refresh();
    expect($recurringTransfer->remaining_recurrences)->toBe(4);
});

test('it deletes recurring transfer when remaining recurrences reaches zero', function () {
    $recurringTransfer = RecurringTransfer::factory()
        ->forUser($this->user)
        ->toAccount($this->creditorAccount)
        ->fromAccount($this->debtorAccount)
        ->create([
            'next_transaction_at' => now()->subDay(),
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => 1,
        ]);

    (new TransactRecurringTransferJob)->handle();

    expect(Transfer::count())->toBe(1);
    expect(RecurringTransfer::find($recurringTransfer->id))->toBeNull();
});

test('it does not delete recurring transfer with unlimited recurrences', function () {
    $recurringTransfer = RecurringTransfer::factory()
        ->forUser($this->user)
        ->toAccount($this->creditorAccount)
        ->fromAccount($this->debtorAccount)
        ->unlimited()
        ->create([
            'next_transaction_at' => now()->subDay(),
            'frequency' => Frequency::Monthly,
        ]);

    (new TransactRecurringTransferJob)->handle();

    expect(Transfer::count())->toBe(1);
    expect(RecurringTransfer::find($recurringTransfer->id))->not->toBeNull();
});

test('it copies tags from recurring transfer to transfer', function () {
    $tags = TagFactory::new()->count(3)->create();

    $recurringTransfer = RecurringTransfer::factory()
        ->forUser($this->user)
        ->toAccount($this->creditorAccount)
        ->fromAccount($this->debtorAccount)
        ->create([
            'next_transaction_at' => now()->subDay(),
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => null,
        ]);

    $recurringTransfer->tags()->sync($tags->pluck('id'));

    (new TransactRecurringTransferJob)->handle();

    $transfer = Transfer::first();
    expect($transfer->tags)->toHaveCount(3);
    expect($transfer->tags->pluck('id')->toArray())->toBe($tags->pluck('id')->toArray());
});

test('it processes multiple due recurring transfers', function () {
    RecurringTransfer::factory()
        ->count(3)
        ->forUser($this->user)
        ->toAccount($this->creditorAccount)
        ->fromAccount($this->debtorAccount)
        ->create([
            'next_transaction_at' => now()->subDay(),
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => null,
        ]);

    (new TransactRecurringTransferJob)->handle();

    expect(Transfer::count())->toBe(3);
});

test('it sets transacted_at to the scheduled date', function () {
    $scheduledDate = now()->subDays(3);

    $recurringTransfer = RecurringTransfer::factory()
        ->forUser($this->user)
        ->toAccount($this->creditorAccount)
        ->fromAccount($this->debtorAccount)
        ->create([
            'next_transaction_at' => $scheduledDate,
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => null,
        ]);

    (new TransactRecurringTransferJob)->handle();

    $transfer = Transfer::first();
    expect($transfer->transacted_at->toDateString())->toBe($scheduledDate->toDateString());
});

test('it copies creditor and debtor accounts from recurring transfer', function () {
    $recurringTransfer = RecurringTransfer::factory()
        ->forUser($this->user)
        ->toAccount($this->creditorAccount)
        ->fromAccount($this->debtorAccount)
        ->create([
            'next_transaction_at' => now()->subDay(),
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => null,
        ]);

    (new TransactRecurringTransferJob)->handle();

    $transfer = Transfer::first();
    expect($transfer->creditor_id)->toBe($this->creditorAccount->id)
        ->and($transfer->debtor_id)->toBe($this->debtorAccount->id);
});
