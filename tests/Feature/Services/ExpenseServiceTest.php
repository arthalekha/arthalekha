<?php

use App\Models\Account;
use App\Models\Balance;
use App\Models\Expense;
use App\Models\User;
use App\Services\AccountService;
use App\Services\ExpenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    Date::setTestNow(now()->setDay(15));

    $accountData = Account::factory()->for($this->user)->ofSameBalances(100)->make([
        'initial_date' => today()->subDays(60),
    ]);

    $this->account = app(AccountService::class)->createAccount($this->user, $accountData->toArray());
    $this->expenseService = app(ExpenseService::class);
});

it('updates current balance when expense is created, updated and deleted', function () {
    $expenseData = Expense::factory()
        ->for($this->account)
        ->make([
            'amount' => 100,
            'transacted_at' => today()->subDays(30),
        ]);

    $expense = $this->expenseService->createExpense($this->user, $expenseData->toArray());

    assertDatabaseHas(Balance::class, [
        'account_id' => $this->account->id,
        'recorded_until' => today()->subDays(30)->endOfMonth()->toDateString(),
        'balance' => 0,
    ]);
    assertDatabaseHas(Balance::class, [
        'account_id' => $this->account->id,
        'recorded_until' => today()->subDays(60)->endOfMonth()->toDateString(),
        'balance' => 100,
    ]);

    $data = [
        'amount' => 200,
        'transacted_at' => today()->subDays(60),
    ];

    $expense = $this->expenseService->updateExpense($expense, $data);

    assertDatabaseHas(Balance::class, [
        'account_id' => $this->account->id,
        'recorded_until' => today()->subDays(30)->endOfMonth()->toDateString(),
        'balance' => -100,
    ]);
    assertDatabaseHas(Balance::class, [
        'account_id' => $this->account->id,
        'recorded_until' => today()->subDays(60)->endOfMonth()->toDateString(),
        'balance' => -100,
    ]);

    $this->expenseService->deleteExpense($expense);

    assertDatabaseHas(Balance::class, [
        'account_id' => $this->account->id,
        'recorded_until' => today()->subDays(30)->endOfMonth()->toDateString(),
        'balance' => 100,
    ]);
    assertDatabaseHas(Balance::class, [
        'account_id' => $this->account->id,
        'recorded_until' => today()->subDays(60)->endOfMonth()->toDateString(),
        'balance' => 100,
    ]);
});
