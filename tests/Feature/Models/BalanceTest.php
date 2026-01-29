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

test('balance belongs to account', function () {
    $balance = Balance::factory()->forAccount($this->account)->create();

    expect($balance->account)->toBeInstanceOf(Account::class);
    expect($balance->account->id)->toBe($this->account->id);
});

test('account has many balances', function () {
    Balance::factory()->count(3)->forAccount($this->account)->create();

    expect($this->account->balances)->toHaveCount(3);
    expect($this->account->balances->first())->toBeInstanceOf(Balance::class);
});

test('balance casts to decimal with 2 places', function () {
    $balance = Balance::factory()->forAccount($this->account)->create([
        'balance' => 1234.56,
    ]);

    expect($balance->balance)->toBe('1234.56');
});

test('recorded_until casts to Carbon date', function () {
    $balance = Balance::factory()->forAccount($this->account)->create([
        'recorded_until' => '2024-12-31',
    ]);

    expect($balance->recorded_until)->toBeInstanceOf(\Carbon\CarbonInterface::class);
    expect($balance->recorded_until->format('Y-m-d'))->toBe('2024-12-31');
});

test('balance is deleted when account is force deleted', function () {
    $balance = Balance::factory()->forAccount($this->account)->create();
    $balanceId = $balance->id;

    $this->account->forceDelete();

    expect(Balance::find($balanceId))->toBeNull();
});

test('forAccount factory state works correctly', function () {
    $balance = Balance::factory()->forAccount($this->account)->create();

    expect($balance->account_id)->toBe($this->account->id);
});

test('recordedUntil factory state works correctly', function () {
    $balance = Balance::factory()
        ->forAccount($this->account)
        ->recordedUntil('2024-06-30')
        ->create();

    expect($balance->recorded_until->format('Y-m-d'))->toBe('2024-06-30');
});

test('unique constraint prevents duplicate balance for same account and date', function () {
    Balance::factory()->forAccount($this->account)->create([
        'recorded_until' => '2024-12-31',
    ]);

    expect(fn () => Balance::factory()->forAccount($this->account)->create([
        'recorded_until' => '2024-12-31',
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});

test('same date can be used for different accounts', function () {
    $anotherAccount = Account::factory()->forUser($this->user)->create();

    Balance::factory()->forAccount($this->account)->create([
        'recorded_until' => '2024-12-31',
    ]);

    $balance = Balance::factory()->forAccount($anotherAccount)->create([
        'recorded_until' => '2024-12-31',
    ]);

    expect($balance)->toBeInstanceOf(Balance::class);
});

test('decimal precision is maintained for balance', function () {
    $balance = Balance::factory()->forAccount($this->account)->create([
        'balance' => '9999999.99',
    ]);

    expect($balance->balance)->toBe('9999999.99');
});

test('balance can be negative', function () {
    $balance = Balance::factory()->forAccount($this->account)->create([
        'balance' => -500.00,
    ]);

    expect($balance->balance)->toBe('-500.00');
});
