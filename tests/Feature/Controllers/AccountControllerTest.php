<?php

use App\Enums\AccountType;
use App\Enums\Frequency;
use App\Models\Account;
use App\Models\Balance;
use App\Models\Income;
use App\Models\User;
use Carbon\Carbon;
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
    $ownAccount = Account::factory()->forUser($this->user)->create(['name' => 'My Own Account']);
    $otherAccount = Account::factory()->create(['name' => 'Other User Account']);

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
        'initial_date' => '2025-01-01',
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
            'initial_date' => '2025-01-01',
        ])
        ->assertSessionHasErrors('name');
});

test('creating an account requires a valid account type', function () {
    $this->actingAs($this->user)
        ->post(route('accounts.store'), [
            'name' => 'Test Account',
            'account_type' => 'invalid_type',
            'initial_balance' => 0,
            'initial_date' => '2025-01-01',
        ])
        ->assertSessionHasErrors('account_type');
});

test('creating an account requires initial_date', function () {
    $this->actingAs($this->user)
        ->post(route('accounts.store'), [
            'name' => 'Test Account',
            'account_type' => AccountType::Savings->value,
            'initial_balance' => 0,
        ])
        ->assertSessionHasErrors('initial_date');
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
    $account = Account::factory()
        ->forUser($this->user)
        ->ofType(AccountType::Savings)
        ->create();

    $updatedData = [
        'name' => 'Updated Account',
        'identifier' => 'NEW-ID',
        'initial_balance' => 2000.00,
        'initial_date' => '2025-02-01',
    ];

    $this->actingAs($this->user)
        ->put(route('accounts.update', $account), $updatedData)
        ->assertRedirect(route('accounts.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('accounts', [
        'id' => $account->id,
        'name' => 'Updated Account',
        'account_type' => AccountType::Savings->value,
    ]);
});

test('user cannot update another users account', function () {
    $otherUser = User::factory()->create();
    $account = Account::factory()->forUser($otherUser)->create();

    $this->actingAs($this->user)
        ->put(route('accounts.update', $account), [
            'name' => 'Hacked Account',
            'initial_balance' => 0,
            'initial_date' => '2025-01-01',
        ])
        ->assertForbidden();
});

test('account type cannot be changed during update', function () {
    $account = Account::factory()
        ->forUser($this->user)
        ->ofType(AccountType::Savings)
        ->create();

    $this->actingAs($this->user)
        ->put(route('accounts.update', $account), [
            'name' => 'Updated Account',
            'account_type' => AccountType::CreditCard->value,
            'initial_balance' => 1000.00,
            'initial_date' => '2025-01-01',
        ])
        ->assertRedirect(route('accounts.index'));

    $account->refresh();
    expect($account->account_type)->toBe(AccountType::Savings);
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

test('authenticated user can create a savings account with additional data', function () {
    $accountData = [
        'name' => 'Savings Account',
        'account_type' => AccountType::Savings->value,
        'identifier' => '1234567890',
        'initial_balance' => 10000.00,
        'initial_date' => '2025-01-01',
        'data' => [
            'rate_of_interest' => 5.5,
            'interest_frequency' => Frequency::Monthly->value,
            'average_balance_frequency' => Frequency::Quarterly->value,
            'average_balance_amount' => 5000.00,
        ],
    ];

    $this->actingAs($this->user)
        ->post(route('accounts.store'), $accountData)
        ->assertRedirect(route('accounts.index'))
        ->assertSessionHas('success');

    $account = Account::where('name', 'Savings Account')->first();
    expect($account->data)->toEqual([
        'rate_of_interest' => 5.5,
        'interest_frequency' => Frequency::Monthly->value,
        'average_balance_frequency' => Frequency::Quarterly->value,
        'average_balance_amount' => 5000.00,
    ]);
});

test('authenticated user can create a credit card account with additional data', function () {
    $accountData = [
        'name' => 'Credit Card',
        'account_type' => AccountType::CreditCard->value,
        'identifier' => '4111111111111111',
        'initial_balance' => 0,
        'initial_date' => '2025-01-01',
        'data' => [
            'rate_of_interest' => 24.0,
            'interest_frequency' => Frequency::Monthly->value,
            'bill_generated_on' => 15,
            'repayment_of_bill_after_days' => 20,
        ],
    ];

    $this->actingAs($this->user)
        ->post(route('accounts.store'), $accountData)
        ->assertRedirect(route('accounts.index'))
        ->assertSessionHas('success');

    $account = Account::where('name', 'Credit Card')->first();
    expect($account->data)->toEqual([
        'rate_of_interest' => 24.0,
        'interest_frequency' => Frequency::Monthly->value,
        'bill_generated_on' => 15,
        'repayment_of_bill_after_days' => 20,
    ]);
});

test('authenticated user can update savings account with additional data', function () {
    $account = Account::factory()
        ->forUser($this->user)
        ->ofType(AccountType::Savings)
        ->create();

    $updatedData = [
        'name' => 'Updated Savings',
        'initial_balance' => 15000.00,
        'initial_date' => '2025-02-01',
        'data' => [
            'rate_of_interest' => 6.0,
            'interest_frequency' => Frequency::Quarterly->value,
            'average_balance_frequency' => Frequency::Monthly->value,
            'average_balance_amount' => 10000.00,
        ],
    ];

    $this->actingAs($this->user)
        ->put(route('accounts.update', $account), $updatedData)
        ->assertRedirect(route('accounts.index'))
        ->assertSessionHas('success');

    $account->refresh();
    expect($account->data)->toEqual([
        'rate_of_interest' => 6.0,
        'interest_frequency' => Frequency::Quarterly->value,
        'average_balance_frequency' => Frequency::Monthly->value,
        'average_balance_amount' => 10000.00,
    ]);
});

test('savings account validation rejects invalid rate of interest', function () {
    $this->actingAs($this->user)
        ->post(route('accounts.store'), [
            'name' => 'Test Savings',
            'account_type' => AccountType::Savings->value,
            'initial_balance' => 1000,
            'initial_date' => '2025-01-01',
            'data' => [
                'rate_of_interest' => 150,
            ],
        ])
        ->assertSessionHasErrors('data.rate_of_interest');
});

test('credit card validation rejects invalid bill generated on day', function () {
    $this->actingAs($this->user)
        ->post(route('accounts.store'), [
            'name' => 'Test CC',
            'account_type' => AccountType::CreditCard->value,
            'initial_balance' => 0,
            'initial_date' => '2025-01-01',
            'data' => [
                'bill_generated_on' => 32,
            ],
        ])
        ->assertSessionHasErrors('data.bill_generated_on');
});

test('create form shows frequencies for account type selection', function () {
    $this->actingAs($this->user)
        ->get(route('accounts.create'))
        ->assertSuccessful()
        ->assertViewHas('frequencies');
});

test('edit form shows frequencies for account type selection', function () {
    $account = Account::factory()->forUser($this->user)->create();

    $this->actingAs($this->user)
        ->get(route('accounts.edit', $account))
        ->assertSuccessful()
        ->assertViewHas('frequencies');
});

test('show page displays savings account data', function () {
    $account = Account::factory()
        ->forUser($this->user)
        ->ofType(AccountType::Savings)
        ->create([
            'data' => [
                'rate_of_interest' => 5.5,
                'interest_frequency' => Frequency::Monthly->value,
                'average_balance_frequency' => Frequency::Quarterly->value,
                'average_balance_amount' => 10000.00,
            ],
        ]);

    $this->actingAs($this->user)
        ->get(route('accounts.show', $account))
        ->assertSuccessful()
        ->assertSee('Savings Account Details')
        ->assertSee('5.5%')
        ->assertSee('Monthly')
        ->assertSee('Quarterly')
        ->assertSee('10,000.00');
});

test('show page displays credit card account data', function () {
    $account = Account::factory()
        ->forUser($this->user)
        ->ofType(AccountType::CreditCard)
        ->create([
            'data' => [
                'rate_of_interest' => 24.0,
                'interest_frequency' => Frequency::Monthly->value,
                'bill_generated_on' => 15,
                'repayment_of_bill_after_days' => 20,
            ],
        ]);

    $this->actingAs($this->user)
        ->get(route('accounts.show', $account))
        ->assertSuccessful()
        ->assertSee('Credit Card Details')
        ->assertSee('24%')
        ->assertSee('Day 15 of each month')
        ->assertSee('20 days');
});

test('show page displays monthly average balance for savings account when previous month balance exists', function () {
    Carbon::setTestNow('2024-02-15');

    $account = Account::factory()
        ->forUser($this->user)
        ->ofType(AccountType::Savings)
        ->create(['current_balance' => 2000.00]);

    Balance::factory()->forAccount($account)->create([
        'balance' => 1000.00,
        'recorded_until' => '2024-01-31',
    ]);

    // Add current month income to test the calculation
    Income::factory()->forUser($this->user)->forAccount($account)->create([
        'amount' => 500.00,
        'transacted_at' => '2024-02-10',
    ]);

    // Service calculates daily averages from Feb 1 to Feb 15 (15 days):
    // Feb 1-9: 1000 each (9 days), Feb 10: 1000+500=1500, Feb 11-15: 1000 each (5 days)
    // Sum = 9*1000 + 1500 + 5*1000 = 15500, Average = 15500/15 = 1033.33
    $this->actingAs($this->user)
        ->get(route('accounts.show', $account))
        ->assertSuccessful()
        ->assertSee('Monthly Avg Balance')
        ->assertSee('1,033.33')
        ->assertSee('Based on previous month');
});

test('show page displays zero monthly average balance for savings account when no previous month balance exists', function () {
    Carbon::setTestNow('2024-02-15');

    $account = Account::factory()
        ->forUser($this->user)
        ->ofType(AccountType::Savings)
        ->create(['current_balance' => 2000.00]);

    // Service returns 0 when no previous month balance exists (defaults to 0)
    // Daily average of 0 for 15 days = 0
    $this->actingAs($this->user)
        ->get(route('accounts.show', $account))
        ->assertSuccessful()
        ->assertSee('Monthly Avg Balance')
        ->assertViewHas('monthlyAverageBalance', 0.0);
});

test('show page does not display monthly average balance for non-savings accounts', function () {
    Carbon::setTestNow('2024-02-15');

    $account = Account::factory()
        ->forUser($this->user)
        ->ofType(AccountType::CreditCard)
        ->create(['current_balance' => 2000.00]);

    Balance::factory()->forAccount($account)->create([
        'balance' => 1000.00,
        'recorded_until' => '2024-01-31',
    ]);

    $this->actingAs($this->user)
        ->get(route('accounts.show', $account))
        ->assertSuccessful()
        ->assertDontSee('Monthly Avg Balance')
        ->assertViewHas('monthlyAverageBalance', null);
});

test('show page passes monthly average balance to view for savings account', function () {
    Carbon::setTestNow('2024-02-15');

    $account = Account::factory()
        ->forUser($this->user)
        ->ofType(AccountType::Savings)
        ->create(['current_balance' => 3000.00]);

    Balance::factory()->forAccount($account)->create([
        'balance' => 1000.00,
        'recorded_until' => '2024-01-31',
    ]);

    // Add current month transactions
    Income::factory()->forUser($this->user)->forAccount($account)->create([
        'amount' => 1000.00,
        'transacted_at' => '2024-02-10',
    ]);

    // Service calculates daily averages from Feb 1 to Feb 15 (15 days):
    // Feb 1-9: 1000 each (9 days), Feb 10: 1000+1000=2000, Feb 11-15: 1000 each (5 days)
    // Sum = 9*1000 + 2000 + 5*1000 = 16000, Average = 16000/15 = 1066.67
    $this->actingAs($this->user)
        ->get(route('accounts.show', $account))
        ->assertSuccessful()
        ->assertViewHas('monthlyAverageBalance', fn ($value) => abs($value - 1066.6666666666667) < 0.01);
});
