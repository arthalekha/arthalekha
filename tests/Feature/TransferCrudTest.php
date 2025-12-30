<?php

use App\Models\Account;
use App\Models\Tag;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->sourceAccount = Account::factory()->forUser($this->user)->create(['name' => 'Source Account']);
    $this->destinationAccount = Account::factory()->forUser($this->user)->create(['name' => 'Destination Account']);
});

test('guest cannot access transfers index', function () {
    $this->get(route('transfers.index'))
        ->assertRedirect(route('login'));
});

test('authenticated user can view transfers index', function () {
    Transfer::factory()
        ->count(3)
        ->forUser($this->user)
        ->fromAccount($this->sourceAccount)
        ->toAccount($this->destinationAccount)
        ->create();

    $this->actingAs($this->user)
        ->get(route('transfers.index'))
        ->assertSuccessful()
        ->assertViewIs('transfers.index')
        ->assertViewHas('transfers');
});

test('user can only see their own transfers', function () {
    $ownTransfer = Transfer::factory()
        ->forUser($this->user)
        ->fromAccount($this->sourceAccount)
        ->toAccount($this->destinationAccount)
        ->create();
    $otherTransfer = Transfer::factory()->create();

    $this->actingAs($this->user)
        ->get(route('transfers.index'))
        ->assertSuccessful()
        ->assertSee($ownTransfer->description)
        ->assertDontSee($otherTransfer->description);
});

test('authenticated user can view create transfer form', function () {
    $this->actingAs($this->user)
        ->get(route('transfers.create'))
        ->assertSuccessful()
        ->assertViewIs('transfers.create')
        ->assertViewHas('accounts');
});

test('authenticated user can create a transfer', function () {
    $transferData = [
        'creditor_id' => $this->destinationAccount->id,
        'debtor_id' => $this->sourceAccount->id,
        'description' => 'Test Transfer',
        'transacted_at' => now()->format('Y-m-d H:i:s'),
        'amount' => 500.00,
    ];

    $this->actingAs($this->user)
        ->post(route('transfers.store'), $transferData)
        ->assertRedirect(route('transfers.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('transfers', [
        'description' => 'Test Transfer',
        'user_id' => $this->user->id,
        'creditor_id' => $this->destinationAccount->id,
        'debtor_id' => $this->sourceAccount->id,
    ]);
});

test('creating a transfer requires different accounts', function () {
    $this->actingAs($this->user)
        ->post(route('transfers.store'), [
            'creditor_id' => $this->sourceAccount->id,
            'debtor_id' => $this->sourceAccount->id,
            'description' => 'Test Transfer',
            'transacted_at' => now()->format('Y-m-d H:i:s'),
            'amount' => 100,
        ])
        ->assertSessionHasErrors(['creditor_id', 'debtor_id']);
});

test('creating a transfer requires a description', function () {
    $this->actingAs($this->user)
        ->post(route('transfers.store'), [
            'creditor_id' => $this->destinationAccount->id,
            'debtor_id' => $this->sourceAccount->id,
            'description' => '',
            'transacted_at' => now()->format('Y-m-d H:i:s'),
            'amount' => 100,
        ])
        ->assertSessionHasErrors('description');
});

test('creating a transfer requires a valid amount', function () {
    $this->actingAs($this->user)
        ->post(route('transfers.store'), [
            'creditor_id' => $this->destinationAccount->id,
            'debtor_id' => $this->sourceAccount->id,
            'description' => 'Test',
            'transacted_at' => now()->format('Y-m-d H:i:s'),
            'amount' => 0,
        ])
        ->assertSessionHasErrors('amount');
});

test('creating a transfer requires users own accounts', function () {
    $otherAccount = Account::factory()->create();

    $this->actingAs($this->user)
        ->post(route('transfers.store'), [
            'creditor_id' => $otherAccount->id,
            'debtor_id' => $this->sourceAccount->id,
            'description' => 'Test',
            'transacted_at' => now()->format('Y-m-d H:i:s'),
            'amount' => 100,
        ])
        ->assertSessionHasErrors('creditor_id');
});

test('authenticated user can view their own transfer', function () {
    $transfer = Transfer::factory()
        ->forUser($this->user)
        ->fromAccount($this->sourceAccount)
        ->toAccount($this->destinationAccount)
        ->create();

    $this->actingAs($this->user)
        ->get(route('transfers.show', $transfer))
        ->assertSuccessful()
        ->assertViewIs('transfers.show')
        ->assertViewHas('transfer');
});

test('user cannot view another users transfer', function () {
    $otherTransfer = Transfer::factory()->create();

    $this->actingAs($this->user)
        ->get(route('transfers.show', $otherTransfer))
        ->assertForbidden();
});

test('authenticated user can view edit form for their own transfer', function () {
    $transfer = Transfer::factory()
        ->forUser($this->user)
        ->fromAccount($this->sourceAccount)
        ->toAccount($this->destinationAccount)
        ->create();

    $this->actingAs($this->user)
        ->get(route('transfers.edit', $transfer))
        ->assertSuccessful()
        ->assertViewIs('transfers.edit')
        ->assertViewHas('transfer');
});

test('authenticated user can update their own transfer', function () {
    $transfer = Transfer::factory()
        ->forUser($this->user)
        ->fromAccount($this->sourceAccount)
        ->toAccount($this->destinationAccount)
        ->create();

    $updatedData = [
        'creditor_id' => $this->destinationAccount->id,
        'debtor_id' => $this->sourceAccount->id,
        'description' => 'Updated Transfer',
        'transacted_at' => now()->format('Y-m-d H:i:s'),
        'amount' => 1000.00,
    ];

    $this->actingAs($this->user)
        ->put(route('transfers.update', $transfer), $updatedData)
        ->assertRedirect(route('transfers.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('transfers', [
        'id' => $transfer->id,
        'description' => 'Updated Transfer',
    ]);
});

test('user cannot update another users transfer', function () {
    $otherTransfer = Transfer::factory()->create();

    $this->actingAs($this->user)
        ->put(route('transfers.update', $otherTransfer), [
            'creditor_id' => $this->destinationAccount->id,
            'debtor_id' => $this->sourceAccount->id,
            'description' => 'Hacked',
            'transacted_at' => now()->format('Y-m-d H:i:s'),
            'amount' => 100,
        ])
        ->assertForbidden();
});

test('authenticated user can delete their own transfer', function () {
    $transfer = Transfer::factory()
        ->forUser($this->user)
        ->fromAccount($this->sourceAccount)
        ->toAccount($this->destinationAccount)
        ->create();

    $this->actingAs($this->user)
        ->delete(route('transfers.destroy', $transfer))
        ->assertRedirect(route('transfers.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('transfers', ['id' => $transfer->id]);
});

test('user cannot delete another users transfer', function () {
    $otherTransfer = Transfer::factory()->create();

    $this->actingAs($this->user)
        ->delete(route('transfers.destroy', $otherTransfer))
        ->assertForbidden();

    $this->assertDatabaseHas('transfers', ['id' => $otherTransfer->id]);
});

// Tag tests

test('transfer create form includes tags', function () {
    Tag::factory()->count(3)->create();

    $this->actingAs($this->user)
        ->get(route('transfers.create'))
        ->assertSuccessful()
        ->assertViewHas('tags');
});

test('transfer edit form includes tags', function () {
    $transfer = Transfer::factory()
        ->forUser($this->user)
        ->fromAccount($this->sourceAccount)
        ->toAccount($this->destinationAccount)
        ->create();
    Tag::factory()->count(3)->create();

    $this->actingAs($this->user)
        ->get(route('transfers.edit', $transfer))
        ->assertSuccessful()
        ->assertViewHas('tags');
});

test('transfer can be created with tags', function () {
    $tags = Tag::factory()->count(2)->create();

    $transferData = [
        'debtor_id' => $this->sourceAccount->id,
        'creditor_id' => $this->destinationAccount->id,
        'description' => 'Transfer with tags',
        'transacted_at' => now()->format('Y-m-d H:i:s'),
        'amount' => 300.00,
        'tags' => $tags->pluck('id')->toArray(),
    ];

    $this->actingAs($this->user)
        ->post(route('transfers.store'), $transferData)
        ->assertRedirect(route('transfers.index'));

    $transfer = Transfer::where('description', 'Transfer with tags')->first();
    expect($transfer->tags)->toHaveCount(2);
});

test('transfer can be updated with tags', function () {
    $transfer = Transfer::factory()
        ->forUser($this->user)
        ->fromAccount($this->sourceAccount)
        ->toAccount($this->destinationAccount)
        ->create();
    $tags = Tag::factory()->count(3)->create();

    $updatedData = [
        'debtor_id' => $this->sourceAccount->id,
        'creditor_id' => $this->destinationAccount->id,
        'description' => 'Updated with tags',
        'transacted_at' => now()->format('Y-m-d H:i:s'),
        'amount' => 600.00,
        'tags' => $tags->pluck('id')->toArray(),
    ];

    $this->actingAs($this->user)
        ->put(route('transfers.update', $transfer), $updatedData)
        ->assertRedirect(route('transfers.index'));

    $transfer->refresh();
    expect($transfer->tags)->toHaveCount(3);
});

test('transfer tags can be removed on update', function () {
    $transfer = Transfer::factory()
        ->forUser($this->user)
        ->fromAccount($this->sourceAccount)
        ->toAccount($this->destinationAccount)
        ->create();
    $tags = Tag::factory()->count(2)->create();
    $transfer->tags()->attach($tags);

    $updatedData = [
        'debtor_id' => $this->sourceAccount->id,
        'creditor_id' => $this->destinationAccount->id,
        'description' => 'Tags removed',
        'transacted_at' => now()->format('Y-m-d H:i:s'),
        'amount' => 600.00,
        'tags' => [],
    ];

    $this->actingAs($this->user)
        ->put(route('transfers.update', $transfer), $updatedData)
        ->assertRedirect(route('transfers.index'));

    $transfer->refresh();
    expect($transfer->tags)->toHaveCount(0);
});
