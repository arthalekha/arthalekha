<?php

use App\Models\Account;
use App\Models\Expense;
use App\Models\Income;
use App\Models\RecurringExpense;
use App\Models\RecurringIncome;
use App\Models\RecurringTransfer;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->otherUser = User::factory()->create();
});

/*
|--------------------------------------------------------------------------
| FamilyModeController Toggle Tests
|--------------------------------------------------------------------------
*/

test('toggle from regular route to family route', function () {
    $this->actingAs($this->user)
        ->post(route('mode.toggle'), ['route' => 'accounts.index'])
        ->assertRedirect(route('family.accounts.index'));
});

test('toggle from family route to regular route', function () {
    $this->actingAs($this->user)
        ->post(route('mode.toggle'), ['route' => 'family.accounts.index'])
        ->assertRedirect(route('accounts.index'));
});

test('toggle replaces store action with index', function () {
    $this->actingAs($this->user)
        ->post(route('mode.toggle'), ['route' => 'incomes.store'])
        ->assertRedirect(route('family.incomes.index'));
});

test('toggle replaces edit action with index', function () {
    $this->actingAs($this->user)
        ->post(route('mode.toggle'), ['route' => 'expenses.edit'])
        ->assertRedirect(route('family.expenses.index'));
});

test('toggle replaces update action with index', function () {
    $this->actingAs($this->user)
        ->post(route('mode.toggle'), ['route' => 'transfers.update'])
        ->assertRedirect(route('family.transfers.index'));
});

test('toggle redirects to home for invalid route', function () {
    $this->actingAs($this->user)
        ->post(route('mode.toggle'), ['route' => 'invalid.nonexistent.route'])
        ->assertRedirect(route('home'));
});

test('toggle requires authentication', function () {
    $this->post(route('mode.toggle'), ['route' => 'accounts.index'])
        ->assertRedirect(route('login'));
});

test('toggle works for all supported routes', function (string $regularRoute, string $familyRoute) {
    $this->actingAs($this->user)
        ->post(route('mode.toggle'), ['route' => $regularRoute])
        ->assertRedirect(route($familyRoute));

    $this->actingAs($this->user)
        ->post(route('mode.toggle'), ['route' => $familyRoute])
        ->assertRedirect(route($regularRoute));
})->with([
    'accounts' => ['accounts.index', 'family.accounts.index'],
    'incomes' => ['incomes.index', 'family.incomes.index'],
    'expenses' => ['expenses.index', 'family.expenses.index'],
    'transfers' => ['transfers.index', 'family.transfers.index'],
    'recurring-incomes' => ['recurring-incomes.index', 'family.recurring-incomes.index'],
    'recurring-expenses' => ['recurring-expenses.index', 'family.recurring-expenses.index'],
    'recurring-transfers' => ['recurring-transfers.index', 'family.recurring-transfers.index'],
    'projected-dashboard' => ['projected-dashboard', 'family.projected-dashboard'],
]);

/*
|--------------------------------------------------------------------------
| Family Routes - Data Visibility Tests
|--------------------------------------------------------------------------
*/

test('family accounts index shows accounts from all users', function () {
    $ownAccount = Account::factory()->forUser($this->user)->create(['name' => 'My Account']);
    $otherAccount = Account::factory()->forUser($this->otherUser)->create(['name' => 'Other User Account']);

    $this->actingAs($this->user)
        ->get(route('family.accounts.index'))
        ->assertSuccessful()
        ->assertSee($ownAccount->name)
        ->assertSee($otherAccount->name);
});

test('family incomes index shows incomes from all users', function () {
    $account1 = Account::factory()->forUser($this->user)->create();
    $account2 = Account::factory()->forUser($this->otherUser)->create();

    $ownIncome = Income::factory()->forUser($this->user)->forAccount($account1)->create([
        'description' => 'My Income',
        'transacted_at' => now(),
    ]);
    $otherIncome = Income::factory()->forUser($this->otherUser)->forAccount($account2)->create([
        'description' => 'Other User Income',
        'transacted_at' => now(),
    ]);

    $this->actingAs($this->user)
        ->get(route('family.incomes.index'))
        ->assertSuccessful()
        ->assertSee($ownIncome->description)
        ->assertSee($otherIncome->description);
});

test('family expenses index shows expenses from all users', function () {
    $account1 = Account::factory()->forUser($this->user)->create();
    $account2 = Account::factory()->forUser($this->otherUser)->create();

    $ownExpense = Expense::factory()->forUser($this->user)->forAccount($account1)->create([
        'description' => 'My Expense',
        'transacted_at' => now(),
    ]);
    $otherExpense = Expense::factory()->forUser($this->otherUser)->forAccount($account2)->create([
        'description' => 'Other User Expense',
        'transacted_at' => now(),
    ]);

    $this->actingAs($this->user)
        ->get(route('family.expenses.index'))
        ->assertSuccessful()
        ->assertSee($ownExpense->description)
        ->assertSee($otherExpense->description);
});

test('family transfers index shows transfers from all users', function () {
    $account1 = Account::factory()->forUser($this->user)->create();
    $account2 = Account::factory()->forUser($this->user)->create();
    $account3 = Account::factory()->forUser($this->otherUser)->create();
    $account4 = Account::factory()->forUser($this->otherUser)->create();

    $ownTransfer = Transfer::factory()->forUser($this->user)->create([
        'description' => 'My Transfer',
        'debtor_id' => $account1->id,
        'creditor_id' => $account2->id,
        'transacted_at' => now(),
    ]);
    $otherTransfer = Transfer::factory()->forUser($this->otherUser)->create([
        'description' => 'Other User Transfer',
        'debtor_id' => $account3->id,
        'creditor_id' => $account4->id,
        'transacted_at' => now(),
    ]);

    $this->actingAs($this->user)
        ->get(route('family.transfers.index'))
        ->assertSuccessful()
        ->assertSee($ownTransfer->description)
        ->assertSee($otherTransfer->description);
});

test('family recurring incomes index shows recurring incomes from all users', function () {
    $account1 = Account::factory()->forUser($this->user)->create();
    $account2 = Account::factory()->forUser($this->otherUser)->create();

    $ownRecurring = RecurringIncome::factory()->forUser($this->user)->forAccount($account1)->create(['description' => 'My Recurring Income']);
    $otherRecurring = RecurringIncome::factory()->forUser($this->otherUser)->forAccount($account2)->create(['description' => 'Other Recurring Income']);

    $this->actingAs($this->user)
        ->get(route('family.recurring-incomes.index'))
        ->assertSuccessful()
        ->assertSee($ownRecurring->description)
        ->assertSee($otherRecurring->description);
});

test('family recurring expenses index shows recurring expenses from all users', function () {
    $account1 = Account::factory()->forUser($this->user)->create();
    $account2 = Account::factory()->forUser($this->otherUser)->create();

    $ownRecurring = RecurringExpense::factory()->forUser($this->user)->forAccount($account1)->create(['description' => 'My Recurring Expense']);
    $otherRecurring = RecurringExpense::factory()->forUser($this->otherUser)->forAccount($account2)->create(['description' => 'Other Recurring Expense']);

    $this->actingAs($this->user)
        ->get(route('family.recurring-expenses.index'))
        ->assertSuccessful()
        ->assertSee($ownRecurring->description)
        ->assertSee($otherRecurring->description);
});

test('family recurring transfers index shows recurring transfers from all users', function () {
    $account1 = Account::factory()->forUser($this->user)->create();
    $account2 = Account::factory()->forUser($this->user)->create();
    $account3 = Account::factory()->forUser($this->otherUser)->create();
    $account4 = Account::factory()->forUser($this->otherUser)->create();

    $ownRecurring = RecurringTransfer::factory()->forUser($this->user)->create([
        'description' => 'My Recurring Transfer',
        'debtor_id' => $account1->id,
        'creditor_id' => $account2->id,
    ]);
    $otherRecurring = RecurringTransfer::factory()->forUser($this->otherUser)->create([
        'description' => 'Other Recurring Transfer',
        'debtor_id' => $account3->id,
        'creditor_id' => $account4->id,
    ]);

    $this->actingAs($this->user)
        ->get(route('family.recurring-transfers.index'))
        ->assertSuccessful()
        ->assertSee($ownRecurring->description)
        ->assertSee($otherRecurring->description);
});

test('family projected dashboard is accessible', function () {
    $this->actingAs($this->user)
        ->get(route('family.projected-dashboard'))
        ->assertSuccessful();
});

/*
|--------------------------------------------------------------------------
| Regular Routes - Data Isolation Tests (Contrast with Family Routes)
|--------------------------------------------------------------------------
*/

test('regular accounts index only shows own accounts', function () {
    $ownAccount = Account::factory()->forUser($this->user)->create(['name' => 'My Account']);
    $otherAccount = Account::factory()->forUser($this->otherUser)->create(['name' => 'Other User Account']);

    $this->actingAs($this->user)
        ->get(route('accounts.index'))
        ->assertSuccessful()
        ->assertSee($ownAccount->name)
        ->assertDontSee($otherAccount->name);
});

test('regular incomes index only shows own incomes', function () {
    $account1 = Account::factory()->forUser($this->user)->create();
    $account2 = Account::factory()->forUser($this->otherUser)->create();

    $ownIncome = Income::factory()->forUser($this->user)->forAccount($account1)->create([
        'description' => 'My Income',
        'transacted_at' => now(),
    ]);
    $otherIncome = Income::factory()->forUser($this->otherUser)->forAccount($account2)->create([
        'description' => 'Other User Income',
        'transacted_at' => now(),
    ]);

    $this->actingAs($this->user)
        ->get(route('incomes.index'))
        ->assertSuccessful()
        ->assertSee($ownIncome->description)
        ->assertDontSee($otherIncome->description);
});

test('regular expenses index only shows own expenses', function () {
    $account1 = Account::factory()->forUser($this->user)->create();
    $account2 = Account::factory()->forUser($this->otherUser)->create();

    $ownExpense = Expense::factory()->forUser($this->user)->forAccount($account1)->create([
        'description' => 'My Expense',
        'transacted_at' => now(),
    ]);
    $otherExpense = Expense::factory()->forUser($this->otherUser)->forAccount($account2)->create([
        'description' => 'Other User Expense',
        'transacted_at' => now(),
    ]);

    $this->actingAs($this->user)
        ->get(route('expenses.index'))
        ->assertSuccessful()
        ->assertSee($ownExpense->description)
        ->assertDontSee($otherExpense->description);
});

test('regular transfers index only shows own transfers', function () {
    $account1 = Account::factory()->forUser($this->user)->create();
    $account2 = Account::factory()->forUser($this->user)->create();
    $account3 = Account::factory()->forUser($this->otherUser)->create();
    $account4 = Account::factory()->forUser($this->otherUser)->create();

    $ownTransfer = Transfer::factory()->forUser($this->user)->create([
        'description' => 'My Transfer',
        'debtor_id' => $account1->id,
        'creditor_id' => $account2->id,
        'transacted_at' => now(),
    ]);
    $otherTransfer = Transfer::factory()->forUser($this->otherUser)->create([
        'description' => 'Other User Transfer',
        'debtor_id' => $account3->id,
        'creditor_id' => $account4->id,
        'transacted_at' => now(),
    ]);

    $this->actingAs($this->user)
        ->get(route('transfers.index'))
        ->assertSuccessful()
        ->assertSee($ownTransfer->description)
        ->assertDontSee($otherTransfer->description);
});

/*
|--------------------------------------------------------------------------
| Family Routes - Authentication Tests
|--------------------------------------------------------------------------
*/

test('family routes require authentication', function (string $route) {
    $this->get(route($route))
        ->assertRedirect(route('login'));
})->with([
    'family.accounts.index',
    'family.incomes.index',
    'family.expenses.index',
    'family.transfers.index',
    'family.recurring-incomes.index',
    'family.recurring-expenses.index',
    'family.recurring-transfers.index',
    'family.projected-dashboard',
]);

/*
|--------------------------------------------------------------------------
| Family Routes - View Structure Tests
|--------------------------------------------------------------------------
*/

test('family accounts index uses correct view', function () {
    $this->actingAs($this->user)
        ->get(route('family.accounts.index'))
        ->assertViewIs('accounts.index');
});

test('family incomes index uses correct view', function () {
    $this->actingAs($this->user)
        ->get(route('family.incomes.index'))
        ->assertViewIs('incomes.index');
});

test('family expenses index uses correct view', function () {
    $this->actingAs($this->user)
        ->get(route('family.expenses.index'))
        ->assertViewIs('expenses.index');
});

test('family transfers index uses correct view', function () {
    $this->actingAs($this->user)
        ->get(route('family.transfers.index'))
        ->assertViewIs('transfers.index');
});

test('family recurring incomes index uses correct view', function () {
    $this->actingAs($this->user)
        ->get(route('family.recurring-incomes.index'))
        ->assertViewIs('recurring-incomes.index');
});

test('family recurring expenses index uses correct view', function () {
    $this->actingAs($this->user)
        ->get(route('family.recurring-expenses.index'))
        ->assertViewIs('recurring-expenses.index');
});

test('family recurring transfers index uses correct view', function () {
    $this->actingAs($this->user)
        ->get(route('family.recurring-transfers.index'))
        ->assertViewIs('recurring-transfers.index');
});

/*
|--------------------------------------------------------------------------
| Family Mode - Data Count Verification
|--------------------------------------------------------------------------
*/

test('family mode shows correct total count of records', function () {
    // Create accounts for both users
    Account::factory()->count(3)->forUser($this->user)->create();
    Account::factory()->count(2)->forUser($this->otherUser)->create();

    // Regular route should show 3
    $response = $this->actingAs($this->user)->get(route('accounts.index'));
    $regularAccounts = $response->viewData('accounts');
    expect($regularAccounts->total())->toBe(3);

    // Family route should show 5
    $response = $this->actingAs($this->user)->get(route('family.accounts.index'));
    $familyAccounts = $response->viewData('accounts');
    expect($familyAccounts->total())->toBe(5);
});

test('family mode shows all users accounts in dropdown selections', function () {
    $account1 = Account::factory()->forUser($this->user)->create(['name' => 'User1 Savings']);
    $account2 = Account::factory()->forUser($this->otherUser)->create(['name' => 'User2 Checking']);

    // In family incomes index, accounts dropdown should show all accounts
    $response = $this->actingAs($this->user)->get(route('family.incomes.index'));
    $accounts = $response->viewData('accounts');

    expect($accounts)->toHaveCount(2);
    expect($accounts->pluck('name')->toArray())->toContain('User1 Savings', 'User2 Checking');
});
