<?php

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\User;
use App\Services\AccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('account belongs to user', function () {
    $account = Account::factory()->forUser($this->user)->create();

    expect($account->user)->toBeInstanceOf(User::class);
    expect($account->user->id)->toBe($this->user->id);
});

test('account belongs to exactly one user', function () {
    $account = Account::factory()->forUser($this->user)->create();

    expect($account->user_id)->toBe($this->user->id);
    expect($account->user)->not->toBeNull();
});

test('label accessor returns formatted string', function () {
    $account = Account::factory()->forUser($this->user)->create([
        'name' => 'Checking Account',
        'identifier' => '1234',
        'account_type' => AccountType::Savings,
    ]);

    expect($account->label)->toContain('Checking Account');
    expect($account->label)->toContain('1234');
    expect($account->label)->toContain('SB');
});

test('label accessor handles null identifier gracefully', function () {
    $account = Account::factory()->forUser($this->user)->create([
        'name' => 'Cash',
        'identifier' => null,
        'account_type' => AccountType::Cash,
    ]);

    expect($account->label)->toContain('Cash');
    expect($account->label)->toContain('CA');
});

test('label accessor includes AccountType shortCode', function () {
    $account = Account::factory()->forUser($this->user)->create([
        'name' => 'Credit Card',
        'identifier' => '5678',
        'account_type' => AccountType::CreditCard,
    ]);

    expect($account->label)->toStartWith('CC');
    expect($account->label)->toContain('Credit Card');
});

test('account_type casts to AccountType enum', function () {
    $account = Account::factory()->forUser($this->user)->create([
        'account_type' => AccountType::Savings,
    ]);

    expect($account->account_type)->toBeInstanceOf(AccountType::class);
    expect($account->account_type)->toBe(AccountType::Savings);
});

test('current_balance casts to decimal with 2 places', function () {
    $account = Account::factory()->forUser($this->user)->create([
        'current_balance' => 1234.56,
    ]);

    expect($account->current_balance)->toBe('1234.56');
});

test('initial_balance casts to decimal with 2 places', function () {
    $account = Account::factory()->forUser($this->user)->create([
        'initial_balance' => 500.75,
    ]);

    expect($account->initial_balance)->toBe('500.75');
});

test('initial_date casts to Carbon date', function () {
    $account = Account::factory()->forUser($this->user)->create([
        'initial_date' => '2024-01-15',
    ]);

    expect($account->initial_date)->toBeInstanceOf(\Carbon\CarbonInterface::class);
    expect($account->initial_date->format('Y-m-d'))->toBe('2024-01-15');
});

test('initial_date is required', function () {
    $account = Account::factory()->forUser($this->user)->create();

    expect($account->initial_date)->not->toBeNull();
    expect($account->initial_date)->toBeInstanceOf(\Carbon\CarbonInterface::class);
});

test('data casts to array', function () {
    $testData = ['key' => 'value', 'number' => 123];

    $account = Account::factory()->forUser($this->user)->create([
        'data' => $testData,
    ]);

    expect($account->data)->toBeArray();
    expect($account->data)->toBe($testData);
});

test('data can be null', function () {
    $account = Account::factory()->forUser($this->user)->create([
        'data' => null,
    ]);

    expect($account->data)->toBeNull();
});

test('creating account calls AccountService clearCache', function () {
    $accountService = Mockery::mock(AccountService::class);
    $accountService->shouldReceive('clearCache')
        ->once()
        ->with($this->user->id);

    app()->instance(AccountService::class, $accountService);

    Account::factory()->forUser($this->user)->create();
});

test('updating account calls AccountService clearCache', function () {
    $account = Account::factory()->forUser($this->user)->create();

    $accountService = Mockery::mock(AccountService::class);
    $accountService->shouldReceive('clearCache')
        ->once()
        ->with($this->user->id);

    app()->instance(AccountService::class, $accountService);

    $account->update(['name' => 'Updated Name']);
});

test('deleting account calls AccountService clearCache', function () {
    $account = Account::factory()->forUser($this->user)->create();

    $accountService = Mockery::mock(AccountService::class);
    $accountService->shouldReceive('clearCache')
        ->once()
        ->with($this->user->id);

    app()->instance(AccountService::class, $accountService);

    $account->delete();
});

test('forUser factory state works correctly', function () {
    $account = Account::factory()->forUser($this->user)->create();

    expect($account->user_id)->toBe($this->user->id);
    expect($account->user)->toBeInstanceOf(User::class);
});

test('ofType factory state works correctly', function () {
    $account = Account::factory()
        ->forUser($this->user)
        ->ofType(AccountType::CreditCard)
        ->create();

    expect($account->account_type)->toBe(AccountType::CreditCard);
});

test('account type enum maintains all values', function () {
    $types = [
        AccountType::Cash,
        AccountType::Savings,
        AccountType::CreditCard,
        AccountType::Wallet,
        AccountType::Investment,
        AccountType::Loan,
        AccountType::Other,
    ];

    foreach ($types as $type) {
        $account = Account::factory()
            ->forUser($this->user)
            ->ofType($type)
            ->create();

        expect($account->account_type)->toBe($type);
    }
});

test('decimal precision is maintained for balances', function () {
    $account = Account::factory()->forUser($this->user)->create([
        'current_balance' => '9999999.99',
        'initial_balance' => '5555555.55',
    ]);

    expect($account->current_balance)->toBe('9999999.99');
    expect($account->initial_balance)->toBe('5555555.55');
});
