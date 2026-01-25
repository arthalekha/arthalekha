<?php

use App\Enums\Frequency;
use App\Models\Account;
use App\Models\Expense;
use App\Models\Income;
use App\Models\RecurringExpense;
use App\Models\RecurringIncome;
use App\Models\RecurringTransfer;
use App\Models\Transfer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->account = Account::factory()->forUser($this->user)->create([
        'initial_balance' => 1000,
    ]);
});

test('guest cannot access account projected balance', function () {
    $this->get(route('accounts.projected-balance', $this->account))
        ->assertRedirect(route('login'));
});

test('user cannot access another users account projected balance', function () {
    $otherAccount = Account::factory()->create();

    $this->actingAs($this->user)
        ->get(route('accounts.projected-balance', $otherAccount))
        ->assertNotFound();
});

test('authenticated user can view their account projected balance', function () {
    $this->actingAs($this->user)
        ->get(route('accounts.projected-balance', $this->account))
        ->assertSuccessful()
        ->assertViewIs('accounts.projected-balance')
        ->assertViewHas('account')
        ->assertViewHas('filters')
        ->assertViewHas('dailyProjections')
        ->assertViewHas('dates')
        ->assertViewHas('incomeData')
        ->assertViewHas('expenseData')
        ->assertViewHas('balanceData')
        ->assertViewHas('summary');
});

test('default filters to current month', function () {
    $this->actingAs($this->user)
        ->get(route('accounts.projected-balance', $this->account))
        ->assertSuccessful()
        ->assertViewHas('filters', function ($filters) {
            return $filters['from_date'] === now()->startOfMonth()->format('Y-m-d')
                && $filters['to_date'] === now()->endOfMonth()->format('Y-m-d');
        });
});

test('custom date range filtering works', function () {
    Carbon::setTestNow(Carbon::create(2025, 3, 15));

    $this->actingAs($this->user)
        ->get(route('accounts.projected-balance', [
            'account' => $this->account,
            'filter' => [
                'from_date' => '2025-03-01',
                'to_date' => '2025-03-31',
            ],
        ]))
        ->assertSuccessful()
        ->assertViewHas('filters', function ($filters) {
            return $filters['from_date'] === '2025-03-01'
                && $filters['to_date'] === '2025-03-31';
        })
        ->assertViewHas('dates', function ($dates) {
            return count($dates) === 31;
        });
});

test('includes actual incomes in projections', function () {
    Carbon::setTestNow(Carbon::create(2025, 3, 15));

    Income::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create([
            'amount' => 500,
            'transacted_at' => Carbon::create(2025, 3, 10),
        ]);

    $response = $this->actingAs($this->user)
        ->get(route('accounts.projected-balance', [
            'account' => $this->account,
            'filter' => [
                'from_date' => '2025-03-01',
                'to_date' => '2025-03-31',
            ],
        ]));

    $response->assertSuccessful();

    $summary = $response->viewData('summary');
    expect($summary['totalIncome'])->toBe(500.0);
});

test('includes actual expenses in projections', function () {
    Carbon::setTestNow(Carbon::create(2025, 3, 15));

    Expense::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create([
            'amount' => 200,
            'transacted_at' => Carbon::create(2025, 3, 12),
        ]);

    $response = $this->actingAs($this->user)
        ->get(route('accounts.projected-balance', [
            'account' => $this->account,
            'filter' => [
                'from_date' => '2025-03-01',
                'to_date' => '2025-03-31',
            ],
        ]));

    $response->assertSuccessful();

    $summary = $response->viewData('summary');
    expect($summary['totalExpense'])->toBe(200.0);
});

test('includes actual transfers in projections', function () {
    Carbon::setTestNow(Carbon::create(2025, 3, 15));

    $otherAccount = Account::factory()->forUser($this->user)->create();

    Transfer::factory()
        ->forUser($this->user)
        ->create([
            'creditor_id' => $this->account->id,
            'debtor_id' => $otherAccount->id,
            'amount' => 300,
            'transacted_at' => Carbon::create(2025, 3, 5),
        ]);

    Transfer::factory()
        ->forUser($this->user)
        ->create([
            'creditor_id' => $otherAccount->id,
            'debtor_id' => $this->account->id,
            'amount' => 150,
            'transacted_at' => Carbon::create(2025, 3, 8),
        ]);

    $response = $this->actingAs($this->user)
        ->get(route('accounts.projected-balance', [
            'account' => $this->account,
            'filter' => [
                'from_date' => '2025-03-01',
                'to_date' => '2025-03-31',
            ],
        ]));

    $response->assertSuccessful();

    $summary = $response->viewData('summary');
    expect($summary['totalTransferIn'])->toBe(300.0);
    expect($summary['totalTransferOut'])->toBe(150.0);
});

test('projects recurring incomes for this account', function () {
    Carbon::setTestNow(Carbon::create(2025, 3, 1));

    RecurringIncome::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create([
            'amount' => 1000,
            'next_transaction_at' => Carbon::create(2025, 3, 5),
            'frequency' => Frequency::Weekly,
            'remaining_recurrences' => null,
        ]);

    $response = $this->actingAs($this->user)
        ->get(route('accounts.projected-balance', [
            'account' => $this->account,
            'filter' => [
                'from_date' => '2025-03-01',
                'to_date' => '2025-03-31',
            ],
        ]));

    $response->assertSuccessful();

    $summary = $response->viewData('summary');
    expect($summary['totalIncome'])->toBe(4000.0);
});

test('projects recurring expenses for this account', function () {
    Carbon::setTestNow(Carbon::create(2025, 3, 1));

    RecurringExpense::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create([
            'amount' => 500,
            'next_transaction_at' => Carbon::create(2025, 3, 1),
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => null,
        ]);

    $response = $this->actingAs($this->user)
        ->get(route('accounts.projected-balance', [
            'account' => $this->account,
            'filter' => [
                'from_date' => '2025-03-01',
                'to_date' => '2025-03-31',
            ],
        ]));

    $response->assertSuccessful();

    $summary = $response->viewData('summary');
    expect($summary['totalExpense'])->toBe(500.0);
});

test('projects recurring transfers for this account', function () {
    Carbon::setTestNow(Carbon::create(2025, 3, 1));

    $otherAccount = Account::factory()->forUser($this->user)->create();

    RecurringTransfer::factory()
        ->forUser($this->user)
        ->toAccount($this->account)
        ->fromAccount($otherAccount)
        ->create([
            'amount' => 250,
            'next_transaction_at' => Carbon::create(2025, 3, 10),
            'frequency' => Frequency::Biweekly,
            'remaining_recurrences' => null,
        ]);

    RecurringTransfer::factory()
        ->forUser($this->user)
        ->toAccount($otherAccount)
        ->fromAccount($this->account)
        ->create([
            'amount' => 100,
            'next_transaction_at' => Carbon::create(2025, 3, 15),
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => null,
        ]);

    $response = $this->actingAs($this->user)
        ->get(route('accounts.projected-balance', [
            'account' => $this->account,
            'filter' => [
                'from_date' => '2025-03-01',
                'to_date' => '2025-03-31',
            ],
        ]));

    $response->assertSuccessful();

    $summary = $response->viewData('summary');
    expect($summary['totalTransferIn'])->toBe(500.0);
    expect($summary['totalTransferOut'])->toBe(100.0);
});

test('does not include other accounts recurring transactions', function () {
    Carbon::setTestNow(Carbon::create(2025, 3, 1));

    $otherAccount = Account::factory()->forUser($this->user)->create();

    RecurringIncome::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create([
            'amount' => 1000,
            'next_transaction_at' => Carbon::create(2025, 3, 1),
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => null,
        ]);

    RecurringIncome::factory()
        ->forUser($this->user)
        ->forAccount($otherAccount)
        ->create([
            'amount' => 5000,
            'next_transaction_at' => Carbon::create(2025, 3, 1),
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => null,
        ]);

    $response = $this->actingAs($this->user)
        ->get(route('accounts.projected-balance', [
            'account' => $this->account,
            'filter' => [
                'from_date' => '2025-03-01',
                'to_date' => '2025-03-31',
            ],
        ]));

    $response->assertSuccessful();

    $summary = $response->viewData('summary');
    expect($summary['totalIncome'])->toBe(1000.0);
});

test('respects remaining recurrences limit', function () {
    Carbon::setTestNow(Carbon::create(2025, 3, 1));

    RecurringIncome::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create([
            'amount' => 1000,
            'next_transaction_at' => Carbon::create(2025, 3, 1),
            'frequency' => Frequency::Weekly,
            'remaining_recurrences' => 2,
        ]);

    $response = $this->actingAs($this->user)
        ->get(route('accounts.projected-balance', [
            'account' => $this->account,
            'filter' => [
                'from_date' => '2025-03-01',
                'to_date' => '2025-03-31',
            ],
        ]));

    $response->assertSuccessful();

    $summary = $response->viewData('summary');
    expect($summary['totalIncome'])->toBe(2000.0);
});

test('calculates correct ending balance', function () {
    Carbon::setTestNow(Carbon::create(2025, 3, 1));

    $this->account->update(['initial_balance' => 5000]);

    Income::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create([
            'amount' => 1000,
            'transacted_at' => Carbon::create(2025, 3, 10),
        ]);

    Expense::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create([
            'amount' => 300,
            'transacted_at' => Carbon::create(2025, 3, 15),
        ]);

    $response = $this->actingAs($this->user)
        ->get(route('accounts.projected-balance', [
            'account' => $this->account,
            'filter' => [
                'from_date' => '2025-03-01',
                'to_date' => '2025-03-31',
            ],
        ]));

    $response->assertSuccessful();

    $summary = $response->viewData('summary');
    expect($summary['startingBalance'])->toBe(5000.0);
    expect($summary['endingBalance'])->toBe(5700.0);
});

test('provides chart data arrays', function () {
    $response = $this->actingAs($this->user)
        ->get(route('accounts.projected-balance', $this->account));

    $response->assertSuccessful();
    $response->assertViewHas('dates');
    $response->assertViewHas('incomeData');
    $response->assertViewHas('expenseData');
    $response->assertViewHas('transferInData');
    $response->assertViewHas('transferOutData');
    $response->assertViewHas('balanceData');
    $response->assertViewHas('averageBalanceData');

    $dates = $response->viewData('dates');
    $incomeData = $response->viewData('incomeData');
    $expenseData = $response->viewData('expenseData');
    $balanceData = $response->viewData('balanceData');
    $averageBalanceData = $response->viewData('averageBalanceData');

    expect($dates)->toBeArray();
    expect($incomeData)->toBeArray();
    expect($expenseData)->toBeArray();
    expect($balanceData)->toBeArray();
    expect($averageBalanceData)->toBeArray();
    expect(count($incomeData))->toBe(count($dates));
    expect(count($expenseData))->toBe(count($dates));
    expect(count($balanceData))->toBe(count($dates));
    expect(count($averageBalanceData))->toBe(count($dates));
});

test('provides daily breakdown', function () {
    $response = $this->actingAs($this->user)
        ->get(route('accounts.projected-balance', $this->account));

    $response->assertSuccessful();
    $response->assertViewHas('dailyProjections');

    $dailyProjections = $response->viewData('dailyProjections');
    expect($dailyProjections)->toBeArray();

    foreach ($dailyProjections as $date => $data) {
        expect($data)->toHaveKeys(['income', 'expense', 'transfer_in', 'transfer_out', 'balance']);
    }
});

test('starting balance uses previous month balance when available', function () {
    Carbon::setTestNow(Carbon::create(2025, 3, 15));

    $this->account->balances()->create([
        'balance' => 2500,
        'recorded_until' => '2025-02-28',
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('accounts.projected-balance', [
            'account' => $this->account,
            'filter' => [
                'from_date' => '2025-03-01',
                'to_date' => '2025-03-31',
            ],
        ]));

    $response->assertSuccessful();

    $summary = $response->viewData('summary');
    expect($summary['startingBalance'])->toBe(2500.0);
});

test('calculates average balance for the period', function () {
    Carbon::setTestNow(Carbon::create(2025, 3, 1));

    $this->account->update(['initial_balance' => 1000]);

    Income::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create([
            'amount' => 300,
            'transacted_at' => Carbon::create(2025, 3, 2),
        ]);

    $response = $this->actingAs($this->user)
        ->get(route('accounts.projected-balance', [
            'account' => $this->account,
            'filter' => [
                'from_date' => '2025-03-01',
                'to_date' => '2025-03-03',
            ],
        ]));

    $response->assertSuccessful();

    $summary = $response->viewData('summary');
    $balanceData = $response->viewData('balanceData');
    $averageBalanceData = $response->viewData('averageBalanceData');

    // Day 1: 1000, Day 2: 1300, Day 3: 1300 => Average: (1000 + 1300 + 1300) / 3 = 1200
    expect($balanceData)->toBe([1000.0, 1300.0, 1300.0]);
    expect($summary['averageBalance'])->toBe(1200.0);
    expect($averageBalanceData)->toBe([1200.0, 1200.0, 1200.0]);
});
