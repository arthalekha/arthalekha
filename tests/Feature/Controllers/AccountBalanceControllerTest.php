<?php

use App\Models\Account;
use App\Models\Balance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->account = Account::factory()->forUser($this->user)->create();
});

test('guest cannot access account balances', function () {
    $this->get(route('accounts.balances', $this->account))
        ->assertRedirect(route('login'));
});

test('user cannot access another users account balances', function () {
    $otherAccount = Account::factory()->create();

    $this->actingAs($this->user)
        ->get(route('accounts.balances', $otherAccount))
        ->assertForbidden();
});

test('authenticated user can view their account balances', function () {
    $this->actingAs($this->user)
        ->get(route('accounts.balances', $this->account))
        ->assertSuccessful()
        ->assertViewIs('accounts.balances')
        ->assertViewHas('account')
        ->assertViewHas('balances');
});

test('balances page shows empty state when no balances exist', function () {
    $this->actingAs($this->user)
        ->get(route('accounts.balances', $this->account))
        ->assertSuccessful()
        ->assertSee('No historical balances recorded yet');
});

test('balances page displays historical balances', function () {
    Balance::factory()
        ->forAccount($this->account)
        ->create([
            'balance' => 1500.50,
            'recorded_until' => '2024-01-31',
        ]);

    $this->actingAs($this->user)
        ->get(route('accounts.balances', $this->account))
        ->assertSuccessful()
        ->assertSee('1,500.50')
        ->assertSee('January 2024');
});

test('balances are ordered by recorded_until descending', function () {
    Balance::factory()
        ->forAccount($this->account)
        ->create([
            'balance' => 1000.00,
            'recorded_until' => '2024-01-31',
        ]);

    Balance::factory()
        ->forAccount($this->account)
        ->create([
            'balance' => 2000.00,
            'recorded_until' => '2024-02-29',
        ]);

    $this->actingAs($this->user)
        ->get(route('accounts.balances', $this->account))
        ->assertSuccessful()
        ->assertSeeInOrder(['February 2024', 'January 2024']);
});

test('balances do not include other accounts balances', function () {
    $otherAccount = Account::factory()->forUser($this->user)->create();

    Balance::factory()
        ->forAccount($this->account)
        ->create([
            'balance' => 1000.00,
            'recorded_until' => '2024-01-31',
        ]);

    Balance::factory()
        ->forAccount($otherAccount)
        ->create([
            'balance' => 5000.00,
            'recorded_until' => '2024-01-31',
        ]);

    $this->actingAs($this->user)
        ->get(route('accounts.balances', $this->account))
        ->assertSuccessful()
        ->assertSee('1,000.00')
        ->assertDontSee('5,000.00');
});

test('balances page shows current balance in stats', function () {
    $this->account->update(['current_balance' => 3500.75]);

    $this->actingAs($this->user)
        ->get(route('accounts.balances', $this->account))
        ->assertSuccessful()
        ->assertSee('3,500.75');
});

test('balances page shows total records count', function () {
    Balance::factory()
        ->forAccount($this->account)
        ->count(5)
        ->sequence(
            ['recorded_until' => '2024-01-31'],
            ['recorded_until' => '2024-02-29'],
            ['recorded_until' => '2024-03-31'],
            ['recorded_until' => '2024-04-30'],
            ['recorded_until' => '2024-05-31'],
        )
        ->create();

    $this->actingAs($this->user)
        ->get(route('accounts.balances', $this->account))
        ->assertSuccessful()
        ->assertViewHas('balances', function ($balances) {
            return $balances->total() === 5;
        });
});

test('balances are paginated', function () {
    Balance::factory()
        ->forAccount($this->account)
        ->count(15)
        ->sequence(
            ['recorded_until' => '2023-01-31'],
            ['recorded_until' => '2023-02-28'],
            ['recorded_until' => '2023-03-31'],
            ['recorded_until' => '2023-04-30'],
            ['recorded_until' => '2023-05-31'],
            ['recorded_until' => '2023-06-30'],
            ['recorded_until' => '2023-07-31'],
            ['recorded_until' => '2023-08-31'],
            ['recorded_until' => '2023-09-30'],
            ['recorded_until' => '2023-10-31'],
            ['recorded_until' => '2023-11-30'],
            ['recorded_until' => '2023-12-31'],
            ['recorded_until' => '2024-01-31'],
            ['recorded_until' => '2024-02-29'],
            ['recorded_until' => '2024-03-31'],
        )
        ->create();

    $this->actingAs($this->user)
        ->get(route('accounts.balances', $this->account))
        ->assertSuccessful()
        ->assertViewHas('balances', function ($balances) {
            return $balances->count() === 12 && $balances->total() === 15;
        });
});

test('balances page handles negative balances', function () {
    Balance::factory()
        ->forAccount($this->account)
        ->create([
            'balance' => -500.00,
            'recorded_until' => '2024-01-31',
        ]);

    $this->actingAs($this->user)
        ->get(route('accounts.balances', $this->account))
        ->assertSuccessful()
        ->assertSee('-500.00');
});

test('balances page handles zero balance', function () {
    Balance::factory()
        ->forAccount($this->account)
        ->create([
            'balance' => 0.00,
            'recorded_until' => '2024-01-31',
        ]);

    $this->actingAs($this->user)
        ->get(route('accounts.balances', $this->account))
        ->assertSuccessful()
        ->assertSee('0.00');
});
