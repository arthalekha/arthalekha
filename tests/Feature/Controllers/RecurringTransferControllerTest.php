<?php

use App\Enums\Frequency;
use App\Models\Account;
use App\Models\RecurringTransfer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->sourceAccount = Account::factory()->forUser($this->user)->create(['name' => 'Source Account']);
    $this->destinationAccount = Account::factory()->forUser($this->user)->create(['name' => 'Destination Account']);
});

test('guest cannot access recurring transfers index', function () {
    $this->get(route('recurring-transfers.index'))
        ->assertRedirect(route('login'));
});

test('authenticated user can view recurring transfers index', function () {
    RecurringTransfer::factory()
        ->count(3)
        ->forUser($this->user)
        ->fromAccount($this->sourceAccount)
        ->toAccount($this->destinationAccount)
        ->create();

    $this->actingAs($this->user)
        ->get(route('recurring-transfers.index'))
        ->assertSuccessful()
        ->assertViewIs('recurring-transfers.index')
        ->assertViewHas('recurringTransfers');
});

test('user can only see their own recurring transfers', function () {
    $ownRecurringTransfer = RecurringTransfer::factory()
        ->forUser($this->user)
        ->fromAccount($this->sourceAccount)
        ->toAccount($this->destinationAccount)
        ->create();
    $otherRecurringTransfer = RecurringTransfer::factory()->create();

    $this->actingAs($this->user)
        ->get(route('recurring-transfers.index'))
        ->assertSuccessful()
        ->assertSee($ownRecurringTransfer->description)
        ->assertDontSee($otherRecurringTransfer->description);
});

test('authenticated user can view create recurring transfer form', function () {
    $this->actingAs($this->user)
        ->get(route('recurring-transfers.create'))
        ->assertSuccessful()
        ->assertViewIs('recurring-transfers.create')
        ->assertViewHas('accounts')
        ->assertViewHas('frequencies');
});

test('authenticated user can create a recurring transfer', function () {
    $recurringTransferData = [
        'debtor_id' => $this->sourceAccount->id,
        'creditor_id' => $this->destinationAccount->id,
        'description' => 'Monthly Savings',
        'next_transaction_at' => now()->addMonth()->format('Y-m-d H:i:s'),
        'amount' => 500.00,
        'frequency' => Frequency::Monthly->value,
        'remaining_recurrences' => 12,
    ];

    $this->actingAs($this->user)
        ->post(route('recurring-transfers.store'), $recurringTransferData)
        ->assertRedirect(route('recurring-transfers.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('recurring_transfers', [
        'description' => 'Monthly Savings',
        'amount' => 500.00,
        'frequency' => Frequency::Monthly->value,
    ]);
});

test('recurring transfer can be created with unlimited recurrences', function () {
    $recurringTransferData = [
        'debtor_id' => $this->sourceAccount->id,
        'creditor_id' => $this->destinationAccount->id,
        'description' => 'Unlimited Transfer',
        'next_transaction_at' => now()->addMonth()->format('Y-m-d H:i:s'),
        'amount' => 1000.00,
        'frequency' => Frequency::Weekly->value,
        'remaining_recurrences' => null,
    ];

    $this->actingAs($this->user)
        ->post(route('recurring-transfers.store'), $recurringTransferData)
        ->assertRedirect(route('recurring-transfers.index'));

    $this->assertDatabaseHas('recurring_transfers', [
        'description' => 'Unlimited Transfer',
        'remaining_recurrences' => null,
    ]);
});

test('creating a recurring transfer requires a description', function () {
    $recurringTransferData = [
        'debtor_id' => $this->sourceAccount->id,
        'creditor_id' => $this->destinationAccount->id,
        'next_transaction_at' => now()->addMonth()->format('Y-m-d H:i:s'),
        'amount' => 1000.00,
        'frequency' => Frequency::Monthly->value,
    ];

    $this->actingAs($this->user)
        ->post(route('recurring-transfers.store'), $recurringTransferData)
        ->assertSessionHasErrors('description');
});

test('creating a recurring transfer requires a valid amount', function () {
    $recurringTransferData = [
        'debtor_id' => $this->sourceAccount->id,
        'creditor_id' => $this->destinationAccount->id,
        'description' => 'Test',
        'next_transaction_at' => now()->addMonth()->format('Y-m-d H:i:s'),
        'amount' => 0,
        'frequency' => Frequency::Monthly->value,
    ];

    $this->actingAs($this->user)
        ->post(route('recurring-transfers.store'), $recurringTransferData)
        ->assertSessionHasErrors('amount');
});

test('creating a recurring transfer requires a valid frequency', function () {
    $recurringTransferData = [
        'debtor_id' => $this->sourceAccount->id,
        'creditor_id' => $this->destinationAccount->id,
        'description' => 'Test',
        'next_transaction_at' => now()->addMonth()->format('Y-m-d H:i:s'),
        'amount' => 1000.00,
        'frequency' => 'invalid',
    ];

    $this->actingAs($this->user)
        ->post(route('recurring-transfers.store'), $recurringTransferData)
        ->assertSessionHasErrors('frequency');
});

test('creating a recurring transfer requires users own accounts', function () {
    $otherAccount = Account::factory()->create();

    $recurringTransferData = [
        'debtor_id' => $otherAccount->id,
        'creditor_id' => $this->destinationAccount->id,
        'description' => 'Test',
        'next_transaction_at' => now()->addMonth()->format('Y-m-d H:i:s'),
        'amount' => 1000.00,
        'frequency' => Frequency::Monthly->value,
    ];

    $this->actingAs($this->user)
        ->post(route('recurring-transfers.store'), $recurringTransferData)
        ->assertSessionHasErrors('debtor_id');
});

test('creating a recurring transfer requires different source and destination accounts', function () {
    $recurringTransferData = [
        'debtor_id' => $this->sourceAccount->id,
        'creditor_id' => $this->sourceAccount->id,
        'description' => 'Test',
        'next_transaction_at' => now()->addMonth()->format('Y-m-d H:i:s'),
        'amount' => 1000.00,
        'frequency' => Frequency::Monthly->value,
    ];

    $this->actingAs($this->user)
        ->post(route('recurring-transfers.store'), $recurringTransferData)
        ->assertSessionHasErrors(['debtor_id', 'creditor_id']);
});

test('authenticated user can view their own recurring transfer', function () {
    $recurringTransfer = RecurringTransfer::factory()
        ->forUser($this->user)
        ->fromAccount($this->sourceAccount)
        ->toAccount($this->destinationAccount)
        ->create();

    $this->actingAs($this->user)
        ->get(route('recurring-transfers.show', $recurringTransfer))
        ->assertSuccessful()
        ->assertViewIs('recurring-transfers.show')
        ->assertSee($recurringTransfer->description);
});

test('user cannot view another users recurring transfer', function () {
    $otherRecurringTransfer = RecurringTransfer::factory()->create();

    $this->actingAs($this->user)
        ->get(route('recurring-transfers.show', $otherRecurringTransfer))
        ->assertNotFound();
});

test('authenticated user can view edit form for their own recurring transfer', function () {
    $recurringTransfer = RecurringTransfer::factory()
        ->forUser($this->user)
        ->fromAccount($this->sourceAccount)
        ->toAccount($this->destinationAccount)
        ->create();

    $this->actingAs($this->user)
        ->get(route('recurring-transfers.edit', $recurringTransfer))
        ->assertSuccessful()
        ->assertViewIs('recurring-transfers.edit')
        ->assertViewHas('recurringTransfer');
});

test('authenticated user can update their own recurring transfer', function () {
    $recurringTransfer = RecurringTransfer::factory()
        ->forUser($this->user)
        ->fromAccount($this->sourceAccount)
        ->toAccount($this->destinationAccount)
        ->create();

    $updatedData = [
        'debtor_id' => $this->sourceAccount->id,
        'creditor_id' => $this->destinationAccount->id,
        'description' => 'Updated Recurring Transfer',
        'next_transaction_at' => now()->addMonth()->format('Y-m-d H:i:s'),
        'amount' => 3000.00,
        'frequency' => Frequency::Weekly->value,
    ];

    $this->actingAs($this->user)
        ->put(route('recurring-transfers.update', $recurringTransfer), $updatedData)
        ->assertRedirect(route('recurring-transfers.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('recurring_transfers', [
        'id' => $recurringTransfer->id,
        'description' => 'Updated Recurring Transfer',
    ]);
});

test('user cannot update another users recurring transfer', function () {
    $otherRecurringTransfer = RecurringTransfer::factory()->create();

    $this->actingAs($this->user)
        ->put(route('recurring-transfers.update', $otherRecurringTransfer), [
            'debtor_id' => $this->sourceAccount->id,
            'creditor_id' => $this->destinationAccount->id,
            'description' => 'Hacked',
            'next_transaction_at' => now()->addMonth()->format('Y-m-d H:i:s'),
            'amount' => 100,
            'frequency' => Frequency::Monthly->value,
        ])
        ->assertNotFound();
});

test('authenticated user can delete their own recurring transfer', function () {
    $recurringTransfer = RecurringTransfer::factory()
        ->forUser($this->user)
        ->fromAccount($this->sourceAccount)
        ->toAccount($this->destinationAccount)
        ->create();

    $this->actingAs($this->user)
        ->delete(route('recurring-transfers.destroy', $recurringTransfer))
        ->assertRedirect(route('recurring-transfers.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('recurring_transfers', ['id' => $recurringTransfer->id]);
});

test('user cannot delete another users recurring transfer', function () {
    $otherRecurringTransfer = RecurringTransfer::factory()->create();

    $this->actingAs($this->user)
        ->delete(route('recurring-transfers.destroy', $otherRecurringTransfer))
        ->assertNotFound();

    $this->assertDatabaseHas('recurring_transfers', ['id' => $otherRecurringTransfer->id]);
});

test('can filter recurring transfers by search term', function () {
    $matchingTransfer = RecurringTransfer::factory()
        ->forUser($this->user)
        ->fromAccount($this->sourceAccount)
        ->toAccount($this->destinationAccount)
        ->create(['description' => 'Monthly Savings']);

    $nonMatchingTransfer = RecurringTransfer::factory()
        ->forUser($this->user)
        ->fromAccount($this->sourceAccount)
        ->toAccount($this->destinationAccount)
        ->create(['description' => 'Investment Move']);

    $this->actingAs($this->user)
        ->get(route('recurring-transfers.index', ['filter' => ['search' => 'Savings']]))
        ->assertSuccessful()
        ->assertSee('Monthly Savings')
        ->assertDontSee('Investment Move');
});

test('can filter recurring transfers by frequency', function () {
    $monthlyTransfer = RecurringTransfer::factory()
        ->forUser($this->user)
        ->fromAccount($this->sourceAccount)
        ->toAccount($this->destinationAccount)
        ->create(['description' => 'Monthly Transfer', 'frequency' => Frequency::Monthly]);

    $weeklyTransfer = RecurringTransfer::factory()
        ->forUser($this->user)
        ->fromAccount($this->sourceAccount)
        ->toAccount($this->destinationAccount)
        ->create(['description' => 'Weekly Transfer', 'frequency' => Frequency::Weekly]);

    $this->actingAs($this->user)
        ->get(route('recurring-transfers.index', ['filter' => ['frequency' => Frequency::Monthly->value]]))
        ->assertSuccessful()
        ->assertSee('Monthly Transfer')
        ->assertDontSee('Weekly Transfer');
});
