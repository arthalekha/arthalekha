<?php

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('user can have multiple accounts', function () {
    $user = User::factory()->create();
    Account::factory()->count(3)->forUser($user)->create();

    expect($user->accounts)->toHaveCount(3);
    expect($user->accounts->first())->toBeInstanceOf(Account::class);
});

test('creating user has empty accounts collection', function () {
    $user = User::factory()->create();

    expect($user->accounts)->toBeEmpty();
    expect($user->accounts)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
});

test('can create accounts through relationship', function () {
    $user = User::factory()->create();

    $account = $user->accounts()->create([
        'name' => 'Checking',
        'identifier' => '1234',
        'account_type' => \App\Enums\AccountType::Savings,
        'initial_balance' => 1000.00,
    ]);

    expect($account->user_id)->toBe($user->id);
    expect($user->accounts)->toHaveCount(1);
});

test('email_verified_at casts to Carbon datetime', function () {
    $user = User::factory()->create([
        'email_verified_at' => '2024-01-01 10:00:00',
    ]);

    expect($user->email_verified_at)->toBeInstanceOf(\Carbon\CarbonInterface::class);
    expect($user->email_verified_at->format('Y-m-d H:i:s'))->toBe('2024-01-01 10:00:00');
});

test('email_verified_at can be null', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    expect($user->email_verified_at)->toBeNull();
});

test('password is hashed automatically', function () {
    $user = User::factory()->create([
        'password' => 'plaintext-password',
    ]);

    expect($user->password)->not->toBe('plaintext-password');
    expect(Hash::check('plaintext-password', $user->password))->toBeTrue();
});

test('password is hidden from array serialization', function () {
    $user = User::factory()->create();

    $array = $user->toArray();

    expect($array)->not->toHaveKey('password');
});

test('remember_token is hidden from array serialization', function () {
    $user = User::factory()->create([
        'remember_token' => 'test-token',
    ]);

    $array = $user->toArray();

    expect($array)->not->toHaveKey('remember_token');
});

test('mass assignment works for fillable fields', function () {
    $user = User::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
    ]);

    expect($user->name)->toBe('John Doe');
    expect($user->email)->toBe('john@example.com');
    expect(Hash::check('password123', $user->password))->toBeTrue();
});

test('user can be deleted with accounts', function () {
    $user = User::factory()->create();
    Account::factory()->count(2)->forUser($user)->create();

    expect($user->accounts)->toHaveCount(2);

    $user->delete();

    expect(User::find($user->id))->toBeNull();
});
