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
        ->create(['transacted_at' => now()]);
    $otherTransfer = Transfer::factory()->create(['transacted_at' => now()]);

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

// Filter tests

test('transfer index defaults to current month date range', function () {
    $this->actingAs($this->user)
        ->get(route('transfers.index'))
        ->assertSuccessful()
        ->assertViewHas('filters', function ($filters) {
            return $filters['from_date'] === now()->startOfMonth()->format('Y-m-d')
                && $filters['to_date'] === now()->endOfMonth()->format('Y-m-d');
        });
});

test('can filter transfers by tag', function () {
    $tag = Tag::factory()->create();
    $anotherTag = Tag::factory()->create();

    $transferWithTag = Transfer::factory()
        ->forUser($this->user)
        ->fromAccount($this->sourceAccount)
        ->toAccount($this->destinationAccount)
        ->create(['description' => 'Transfer with tag', 'transacted_at' => now()]);
    $transferWithTag->tags()->attach($tag);

    $transferWithAnotherTag = Transfer::factory()
        ->forUser($this->user)
        ->fromAccount($this->sourceAccount)
        ->toAccount($this->destinationAccount)
        ->create(['description' => 'Transfer with another tag', 'transacted_at' => now()]);
    $transferWithAnotherTag->tags()->attach($anotherTag);

    $this->actingAs($this->user)
        ->get(route('transfers.index', ['filter' => ['tag_id' => $tag->id]]))
        ->assertSuccessful()
        ->assertSee('Transfer with tag')
        ->assertDontSee('Transfer with another tag');
});

test('can filter transfers by search term', function () {
    $matchingTransfer = Transfer::factory()
        ->forUser($this->user)
        ->fromAccount($this->sourceAccount)
        ->toAccount($this->destinationAccount)
        ->create(['description' => 'Monthly savings transfer', 'transacted_at' => now()]);

    $nonMatchingTransfer = Transfer::factory()
        ->forUser($this->user)
        ->fromAccount($this->sourceAccount)
        ->toAccount($this->destinationAccount)
        ->create(['description' => 'Emergency fund', 'transacted_at' => now()]);

    $this->actingAs($this->user)
        ->get(route('transfers.index', ['filter' => ['search' => 'savings']]))
        ->assertSuccessful()
        ->assertSee('Monthly savings transfer')
        ->assertDontSee('Emergency fund');
});

test('can filter transfers by debtor account', function () {
    $thirdAccount = Account::factory()->forUser($this->user)->create(['name' => 'Third Account']);

    $fromSourceTransfer = Transfer::factory()
        ->forUser($this->user)
        ->fromAccount($this->sourceAccount)
        ->toAccount($this->destinationAccount)
        ->create(['description' => 'From source account', 'transacted_at' => now()]);

    $fromThirdTransfer = Transfer::factory()
        ->forUser($this->user)
        ->fromAccount($thirdAccount)
        ->toAccount($this->destinationAccount)
        ->create(['description' => 'From third account', 'transacted_at' => now()]);

    $this->actingAs($this->user)
        ->get(route('transfers.index', ['filter' => ['debtor_id' => $this->sourceAccount->id]]))
        ->assertSuccessful()
        ->assertSee('From source account')
        ->assertDontSee('From third account');
});

test('tags and accounts are passed to transfer index view for filter dropdowns', function () {
    Tag::factory()->count(3)->create();

    $this->actingAs($this->user)
        ->get(route('transfers.index'))
        ->assertSuccessful()
        ->assertViewHas('tags')
        ->assertViewHas('accounts');
});

test('tag filter is passed to transfer view', function () {
    $tag = Tag::factory()->create();

    $this->actingAs($this->user)
        ->get(route('transfers.index', ['filter' => ['tag_id' => $tag->id]]))
        ->assertSuccessful()
        ->assertViewHas('filters', function ($filters) use ($tag) {
            return $filters['tag_id'] == $tag->id;
        });
});

// Account balance tests

test('creating a transfer adjusts both account balances', function () {
    $sourceInitialBalance = 1000.00;
    $destinationInitialBalance = 500.00;
    $this->sourceAccount->update(['current_balance' => $sourceInitialBalance]);
    $this->destinationAccount->update(['current_balance' => $destinationInitialBalance]);

    $transferAmount = 300.00;
    $transferData = [
        'debtor_id' => $this->sourceAccount->id,
        'creditor_id' => $this->destinationAccount->id,
        'description' => 'Test Transfer',
        'transacted_at' => now()->format('Y-m-d H:i:s'),
        'amount' => $transferAmount,
    ];

    $this->actingAs($this->user)
        ->post(route('transfers.store'), $transferData)
        ->assertRedirect(route('transfers.index'));

    $this->sourceAccount->refresh();
    $this->destinationAccount->refresh();

    expect($this->sourceAccount->current_balance)->toBe(number_format($sourceInitialBalance - $transferAmount, 2, '.', ''));
    expect($this->destinationAccount->current_balance)->toBe(number_format($destinationInitialBalance + $transferAmount, 2, '.', ''));
});

test('updating a transfer amount adjusts both account balances', function () {
    $sourceInitialBalance = 1000.00;
    $destinationInitialBalance = 500.00;
    $this->sourceAccount->update(['current_balance' => $sourceInitialBalance]);
    $this->destinationAccount->update(['current_balance' => $destinationInitialBalance]);

    $oldAmount = 200.00;
    $transfer = Transfer::factory()
        ->forUser($this->user)
        ->fromAccount($this->sourceAccount)
        ->toAccount($this->destinationAccount)
        ->create(['amount' => $oldAmount]);

    $this->sourceAccount->update(['current_balance' => $sourceInitialBalance - $oldAmount]);
    $this->destinationAccount->update(['current_balance' => $destinationInitialBalance + $oldAmount]);

    $newAmount = 350.00;
    $updatedData = [
        'debtor_id' => $this->sourceAccount->id,
        'creditor_id' => $this->destinationAccount->id,
        'description' => 'Updated Transfer',
        'transacted_at' => now()->format('Y-m-d H:i:s'),
        'amount' => $newAmount,
    ];

    $this->actingAs($this->user)
        ->put(route('transfers.update', $transfer), $updatedData)
        ->assertRedirect(route('transfers.index'));

    $this->sourceAccount->refresh();
    $this->destinationAccount->refresh();

    expect($this->sourceAccount->current_balance)->toBe(number_format($sourceInitialBalance - $newAmount, 2, '.', ''));
    expect($this->destinationAccount->current_balance)->toBe(number_format($destinationInitialBalance + $newAmount, 2, '.', ''));
});

test('updating a transfer to different accounts adjusts all account balances', function () {
    $thirdAccount = Account::factory()->forUser($this->user)->create(['current_balance' => 750.00]);

    $sourceInitialBalance = 1000.00;
    $destinationInitialBalance = 500.00;
    $this->sourceAccount->update(['current_balance' => $sourceInitialBalance]);
    $this->destinationAccount->update(['current_balance' => $destinationInitialBalance]);

    $transferAmount = 200.00;
    $transfer = Transfer::factory()
        ->forUser($this->user)
        ->fromAccount($this->sourceAccount)
        ->toAccount($this->destinationAccount)
        ->create(['amount' => $transferAmount]);

    $this->sourceAccount->update(['current_balance' => $sourceInitialBalance - $transferAmount]);
    $this->destinationAccount->update(['current_balance' => $destinationInitialBalance + $transferAmount]);

    $updatedData = [
        'debtor_id' => $this->destinationAccount->id,
        'creditor_id' => $thirdAccount->id,
        'description' => 'Changed accounts',
        'transacted_at' => now()->format('Y-m-d H:i:s'),
        'amount' => $transferAmount,
    ];

    $this->actingAs($this->user)
        ->put(route('transfers.update', $transfer), $updatedData)
        ->assertRedirect(route('transfers.index'));

    $this->sourceAccount->refresh();
    $this->destinationAccount->refresh();
    $thirdAccount->refresh();

    // Source account: was 800, old transfer reversed (+200) = 1000
    expect($this->sourceAccount->current_balance)->toBe(number_format($sourceInitialBalance, 2, '.', ''));
    // Destination account: was 700, old transfer reversed (-200) = 500, new transfer as debtor (-200) = 300
    expect($this->destinationAccount->current_balance)->toBe(number_format($destinationInitialBalance - $transferAmount, 2, '.', ''));
    // Third account: was 750, new transfer as creditor (+200) = 950
    expect($thirdAccount->current_balance)->toBe(number_format(750.00 + $transferAmount, 2, '.', ''));
});

test('deleting a transfer reverses both account balances', function () {
    $sourceInitialBalance = 1000.00;
    $destinationInitialBalance = 500.00;
    $this->sourceAccount->update(['current_balance' => $sourceInitialBalance]);
    $this->destinationAccount->update(['current_balance' => $destinationInitialBalance]);

    $transferAmount = 300.00;
    $transfer = Transfer::factory()
        ->forUser($this->user)
        ->fromAccount($this->sourceAccount)
        ->toAccount($this->destinationAccount)
        ->create(['amount' => $transferAmount]);

    $this->sourceAccount->update(['current_balance' => $sourceInitialBalance - $transferAmount]);
    $this->destinationAccount->update(['current_balance' => $destinationInitialBalance + $transferAmount]);

    $this->actingAs($this->user)
        ->delete(route('transfers.destroy', $transfer))
        ->assertRedirect(route('transfers.index'));

    $this->sourceAccount->refresh();
    $this->destinationAccount->refresh();

    expect($this->sourceAccount->current_balance)->toBe(number_format($sourceInitialBalance, 2, '.', ''));
    expect($this->destinationAccount->current_balance)->toBe(number_format($destinationInitialBalance, 2, '.', ''));
});

// Export tests

test('guest cannot export transfers', function () {
    $this->post(route('transfers.export'))
        ->assertRedirect(route('login'));
});

test('authenticated user can export transfers to csv', function () {
    Transfer::factory()->count(3)
        ->forUser($this->user)
        ->fromAccount($this->sourceAccount)
        ->toAccount($this->destinationAccount)
        ->create(['transacted_at' => now()]);

    $this->actingAs($this->user)
        ->post(route('transfers.export'))
        ->assertSuccessful()
        ->assertDownload('transfers.csv');
});

test('csv export includes filtered transfer data', function () {
    $inRangeTransfer = Transfer::factory()
        ->forUser($this->user)
        ->fromAccount($this->sourceAccount)
        ->toAccount($this->destinationAccount)
        ->create(['transacted_at' => now(), 'description' => 'In range transfer']);

    $outOfRangeTransfer = Transfer::factory()
        ->forUser($this->user)
        ->fromAccount($this->sourceAccount)
        ->toAccount($this->destinationAccount)
        ->create(['transacted_at' => now()->subMonths(2), 'description' => 'Out of range transfer']);

    $response = $this->actingAs($this->user)
        ->post(route('transfers.export'), [
            'filter' => [
                'from_date' => now()->subDays(7)->format('Y-m-d'),
                'to_date' => now()->addDay()->format('Y-m-d'),
            ],
        ]);

    $response->assertSuccessful()
        ->assertDownload('transfers.csv');
});

test('csv export only includes user own transfers', function () {
    $ownTransfer = Transfer::factory()
        ->forUser($this->user)
        ->fromAccount($this->sourceAccount)
        ->toAccount($this->destinationAccount)
        ->create(['transacted_at' => now()]);

    $otherTransfer = Transfer::factory()->create(['transacted_at' => now()]);

    $this->actingAs($this->user)
        ->post(route('transfers.export'))
        ->assertSuccessful()
        ->assertDownload('transfers.csv');
});
