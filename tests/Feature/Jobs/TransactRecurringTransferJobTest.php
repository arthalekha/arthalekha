<?php

use App\Enums\Frequency;
use App\Jobs\TransactRecurringTransferJob;
use App\Models\Account;
use App\Models\RecurringTransfer;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->creditor = Account::factory()->forUser($this->user)->create();
    $this->debtor = Account::factory()->forUser($this->user)->create();
});

test('recurring transfer creates transfer entry when accounts are set', function () {
    $recurringTransfer = RecurringTransfer::factory()
        ->forUser($this->user)
        ->toAccount($this->creditor)
        ->fromAccount($this->debtor)
        ->create([
            'next_transaction_at' => now()->subDay(),
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => null,
        ]);

    TransactRecurringTransferJob::dispatch();

    expect(Transfer::where('user_id', $this->user->id)->count())->toBe(1);

    $transfer = Transfer::where('user_id', $this->user->id)->first();
    expect($transfer->creditor_id)->toBe($this->creditor->id);
    expect($transfer->debtor_id)->toBe($this->debtor->id);
    expect($transfer->description)->toBe($recurringTransfer->description);
});

test('recurring transfer skips transfer entry when both accounts are null', function () {
    $recurringTransfer = RecurringTransfer::factory()
        ->forUser($this->user)
        ->withoutAccounts()
        ->create([
            'next_transaction_at' => now()->subDay(),
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => null,
        ]);

    TransactRecurringTransferJob::dispatch();

    expect(Transfer::where('user_id', $this->user->id)->count())->toBe(0);
});

test('recurring transfer updates next transaction date when accounts are null', function () {
    $originalNextDate = now()->subDay();

    $recurringTransfer = RecurringTransfer::factory()
        ->forUser($this->user)
        ->withoutAccounts()
        ->create([
            'next_transaction_at' => $originalNextDate,
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => null,
        ]);

    TransactRecurringTransferJob::dispatch();

    $recurringTransfer->refresh();

    expect($recurringTransfer->next_transaction_at->format('Y-m-d'))
        ->toBe($originalNextDate->addMonth()->format('Y-m-d'));
});

test('recurring transfer decrements remaining recurrences when accounts are null', function () {
    $recurringTransfer = RecurringTransfer::factory()
        ->forUser($this->user)
        ->withoutAccounts()
        ->create([
            'next_transaction_at' => now()->subDay(),
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => 5,
        ]);

    TransactRecurringTransferJob::dispatch();

    $recurringTransfer->refresh();

    expect($recurringTransfer->remaining_recurrences)->toBe(4);
    expect(Transfer::where('user_id', $this->user->id)->count())->toBe(0);
});

test('recurring transfer is deleted when remaining recurrences reaches zero with null accounts', function () {
    $recurringTransfer = RecurringTransfer::factory()
        ->forUser($this->user)
        ->withoutAccounts()
        ->create([
            'next_transaction_at' => now()->subDay(),
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => 1,
        ]);

    TransactRecurringTransferJob::dispatch();

    expect(RecurringTransfer::find($recurringTransfer->id))->toBeNull();
    expect(Transfer::where('user_id', $this->user->id)->count())->toBe(0);
});
