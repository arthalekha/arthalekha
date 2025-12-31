<?php

use App\Enums\Frequency;
use App\Models\Account;
use App\Models\RecurringExpense;
use App\Models\RecurringIncome;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->account = Account::factory()->forUser($this->user)->create();
});

test('projected dashboard requires authentication', function () {
    $response = $this->get(route('projected-dashboard'));

    $response->assertRedirect(route('login'));
});

test('authenticated user can view projected dashboard', function () {
    $response = $this->actingAs($this->user)->get(route('projected-dashboard'));

    $response->assertOk();
    $response->assertViewIs('projected-dashboard');
});

test('projected dashboard shows 12 months of data', function () {
    $response = $this->actingAs($this->user)->get(route('projected-dashboard'));

    $response->assertOk();
    $response->assertViewHas('months');

    $months = $response->viewData('months');
    expect($months)->toBeArray();
    expect(count($months))->toBe(13);
});

test('projected dashboard calculates monthly recurring income', function () {
    Carbon::setTestNow(Carbon::create(2025, 1, 15));

    RecurringIncome::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create([
            'amount' => 1000,
            'next_transaction_at' => Carbon::create(2025, 1, 20),
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => null,
        ]);

    $response = $this->actingAs($this->user)->get(route('projected-dashboard'));

    $response->assertOk();

    $totalProjectedIncome = $response->viewData('totalProjectedIncome');
    expect($totalProjectedIncome)->toBe(13000.0);
});

test('projected dashboard calculates weekly recurring expense', function () {
    Carbon::setTestNow(Carbon::create(2025, 1, 1));

    RecurringExpense::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create([
            'amount' => 100,
            'next_transaction_at' => Carbon::create(2025, 1, 1),
            'frequency' => Frequency::Weekly,
            'remaining_recurrences' => 10,
        ]);

    $response = $this->actingAs($this->user)->get(route('projected-dashboard'));

    $response->assertOk();

    $totalProjectedExpense = $response->viewData('totalProjectedExpense');
    expect($totalProjectedExpense)->toBe(1000.0);
});

test('projected dashboard calculates net savings', function () {
    Carbon::setTestNow(Carbon::create(2025, 1, 1));

    RecurringIncome::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create([
            'amount' => 5000,
            'next_transaction_at' => Carbon::create(2025, 1, 1),
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => null,
        ]);

    RecurringExpense::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create([
            'amount' => 2000,
            'next_transaction_at' => Carbon::create(2025, 1, 1),
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => null,
        ]);

    $response = $this->actingAs($this->user)->get(route('projected-dashboard'));

    $response->assertOk();
    $response->assertViewHas('projectedNetSavings', 39000.0);
});

test('projected dashboard only shows data for authenticated user', function () {
    Carbon::setTestNow(Carbon::create(2025, 1, 1));

    $otherUser = User::factory()->create();
    $otherAccount = Account::factory()->forUser($otherUser)->create();

    RecurringIncome::factory()
        ->forUser($otherUser)
        ->forAccount($otherAccount)
        ->create([
            'amount' => 10000,
            'next_transaction_at' => Carbon::create(2025, 1, 1),
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => null,
        ]);

    RecurringIncome::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create([
            'amount' => 1000,
            'next_transaction_at' => Carbon::create(2025, 1, 1),
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => null,
        ]);

    $response = $this->actingAs($this->user)->get(route('projected-dashboard'));

    $response->assertOk();
    $response->assertViewHas('totalProjectedIncome', 13000.0);
});

test('projected dashboard provides chart data arrays', function () {
    $response = $this->actingAs($this->user)->get(route('projected-dashboard'));

    $response->assertOk();
    $response->assertViewHas('months');
    $response->assertViewHas('incomeData');
    $response->assertViewHas('expenseData');
    $response->assertViewHas('balanceData');

    $months = $response->viewData('months');
    $incomeData = $response->viewData('incomeData');
    $expenseData = $response->viewData('expenseData');
    $balanceData = $response->viewData('balanceData');

    expect($months)->toBeArray();
    expect($incomeData)->toBeArray();
    expect($expenseData)->toBeArray();
    expect($balanceData)->toBeArray();
    expect(count($incomeData))->toBe(count($months));
    expect(count($expenseData))->toBe(count($months));
    expect(count($balanceData))->toBe(count($months));
});

test('projected dashboard provides monthly breakdown', function () {
    $response = $this->actingAs($this->user)->get(route('projected-dashboard'));

    $response->assertOk();
    $response->assertViewHas('monthlyProjections');

    $monthlyProjections = $response->viewData('monthlyProjections');
    expect($monthlyProjections)->toBeArray();

    foreach ($monthlyProjections as $month => $data) {
        expect($data)->toHaveKeys(['income', 'expense']);
    }
});

test('projected dashboard respects remaining recurrences limit', function () {
    Carbon::setTestNow(Carbon::create(2025, 1, 1));

    RecurringIncome::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create([
            'amount' => 1000,
            'next_transaction_at' => Carbon::create(2025, 1, 1),
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => 3,
        ]);

    $response = $this->actingAs($this->user)->get(route('projected-dashboard'));

    $response->assertOk();
    $response->assertViewHas('totalProjectedIncome', 3000.0);
});

test('projected dashboard shows current balance', function () {
    $this->account->update(['current_balance' => 10000]);

    $response = $this->actingAs($this->user)->get(route('projected-dashboard'));

    $response->assertOk();
    $response->assertViewHas('currentBalance', '10000.00');
});

test('projected dashboard calculates projected balance correctly', function () {
    Carbon::setTestNow(Carbon::create(2025, 1, 1));

    $this->account->update(['current_balance' => 10000]);

    RecurringIncome::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create([
            'amount' => 5000,
            'next_transaction_at' => Carbon::create(2025, 1, 1),
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => null,
        ]);

    RecurringExpense::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create([
            'amount' => 2000,
            'next_transaction_at' => Carbon::create(2025, 1, 1),
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => null,
        ]);

    $response = $this->actingAs($this->user)->get(route('projected-dashboard'));

    $response->assertOk();

    $balanceData = $response->viewData('balanceData');

    // First month: 10000 + 5000 - 2000 = 13000
    expect($balanceData[0])->toBe(13000.0);
    // Second month: 13000 + 5000 - 2000 = 16000
    expect($balanceData[1])->toBe(16000.0);
    // Third month: 16000 + 5000 - 2000 = 19000
    expect($balanceData[2])->toBe(19000.0);
});
