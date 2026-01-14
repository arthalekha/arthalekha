<?php

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('guest cannot access accounts index', function () {
    $this->get(route('accounts.index'))
        ->assertRedirect(route('login'));
});

test('authenticated user can view accounts index', function () {
    Account::factory()->count(3)->forUser($this->user)->create();

    $this->actingAs($this->user)
        ->get(route('accounts.index'))
        ->assertSuccessful()
        ->assertViewIs('accounts.index')
        ->assertViewHas('accounts');
});

test('user can only see their own accounts', function () {
    $ownAccount = Account::factory()->forUser($this->user)->create();
    $otherAccount = Account::factory()->create();

    $this->actingAs($this->user)
        ->get(route('accounts.index'))
        ->assertSuccessful()
        ->assertSee($ownAccount->name)
        ->assertDontSee($otherAccount->name);
});

test('authenticated user can view create account form', function () {
    $this->actingAs($this->user)
        ->get(route('accounts.create'))
        ->assertSuccessful()
        ->assertViewIs('accounts.create')
        ->assertViewHas('accountTypes');
});

test('authenticated user can create an account', function () {
    $accountData = [
        'name' => 'Test Account',
        'account_type' => AccountType::Savings->value,
        'identifier' => '1234567890',
        'initial_balance' => 1000.50,
        'initial_date' => '2025-01-01',
    ];

    $this->actingAs($this->user)
        ->post(route('accounts.store'), $accountData)
        ->assertRedirect(route('accounts.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('accounts', [
        'name' => 'Test Account',
        'user_id' => $this->user->id,
        'account_type' => AccountType::Savings->value,
    ]);
});

test('creating an account sets current_balance to initial_balance', function () {
    $accountData = [
        'name' => 'Test Account',
        'account_type' => AccountType::Cash->value,
        'initial_balance' => 500.00,
    ];

    $this->actingAs($this->user)
        ->post(route('accounts.store'), $accountData);

    $this->assertDatabaseHas('accounts', [
        'name' => 'Test Account',
        'initial_balance' => 500.00,
        'current_balance' => 500.00,
    ]);
});

test('creating an account requires a name', function () {
    $this->actingAs($this->user)
        ->post(route('accounts.store'), [
            'name' => '',
            'account_type' => AccountType::Savings->value,
            'initial_balance' => 0,
        ])
        ->assertSessionHasErrors('name');
});

test('creating an account requires a valid account type', function () {
    $this->actingAs($this->user)
        ->post(route('accounts.store'), [
            'name' => 'Test Account',
            'account_type' => 'invalid_type',
            'initial_balance' => 0,
        ])
        ->assertSessionHasErrors('account_type');
});

test('authenticated user can view their own account', function () {
    $account = Account::factory()->forUser($this->user)->create();

    $this->actingAs($this->user)
        ->get(route('accounts.show', $account))
        ->assertSuccessful()
        ->assertViewIs('accounts.show')
        ->assertViewHas('account', $account);
});

test('user cannot view another users account', function () {
    $otherUser = User::factory()->create();
    $account = Account::factory()->forUser($otherUser)->create();

    $this->actingAs($this->user)
        ->get(route('accounts.show', $account))
        ->assertForbidden();
});

test('authenticated user can view edit form for their own account', function () {
    $account = Account::factory()->forUser($this->user)->create();

    $this->actingAs($this->user)
        ->get(route('accounts.edit', $account))
        ->assertSuccessful()
        ->assertViewIs('accounts.edit')
        ->assertViewHas('account', $account);
});

test('user cannot edit another users account', function () {
    $otherUser = User::factory()->create();
    $account = Account::factory()->forUser($otherUser)->create();

    $this->actingAs($this->user)
        ->get(route('accounts.edit', $account))
        ->assertForbidden();
});

test('authenticated user can update their own account', function () {
    $account = Account::factory()->forUser($this->user)->create();

    $updatedData = [
        'name' => 'Updated Account',
        'account_type' => AccountType::Wallet->value,
        'identifier' => 'NEW-ID',
        'initial_balance' => 2000.00,
    ];

    $this->actingAs($this->user)
        ->put(route('accounts.update', $account), $updatedData)
        ->assertRedirect(route('accounts.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('accounts', [
        'id' => $account->id,
        'name' => 'Updated Account',
        'account_type' => AccountType::Wallet->value,
    ]);
});

test('user cannot update another users account', function () {
    $otherUser = User::factory()->create();
    $account = Account::factory()->forUser($otherUser)->create();

    $this->actingAs($this->user)
        ->put(route('accounts.update', $account), [
            'name' => 'Hacked Account',
            'account_type' => AccountType::Savings->value,
            'initial_balance' => 0,
        ])
        ->assertForbidden();
});

test('authenticated user can delete their own account', function () {
    $account = Account::factory()->forUser($this->user)->create();

    $this->actingAs($this->user)
        ->delete(route('accounts.destroy', $account))
        ->assertRedirect(route('accounts.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('accounts', ['id' => $account->id]);
});

test('user cannot delete another users account', function () {
    $otherUser = User::factory()->create();
    $account = Account::factory()->forUser($otherUser)->create();

    $this->actingAs($this->user)
        ->delete(route('accounts.destroy', $account))
        ->assertForbidden();

    $this->assertDatabaseHas('accounts', ['id' => $account->id]);
});
