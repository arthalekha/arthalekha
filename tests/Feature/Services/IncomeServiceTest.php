<?php

use App\Models\Account;
use App\Models\Balance;
use App\Models\Income;
use App\Models\User;
use App\Services\AccountService;
use App\Services\IncomeService;
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
    $this->incomeService = app(IncomeService::class);
});

it('updates current balance when income is created, updated and deleted', function () {
    $incomeData = Income::factory()
        ->for($this->account)
        ->make([
            'amount' => 100,
            'transacted_at' => today()->subDays(30),
        ]);

    $income = $this->incomeService->createIncome($this->user, $incomeData->toArray());

    assertDatabaseHas(Balance::class, [
        'account_id' => $this->account->id,
        'recorded_until' => today()->subDays(30)->endOfMonth()->toDateString(),
        'balance' => 200,
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

    $income = $this->incomeService->updateIncome($income, $data);

    assertDatabaseHas(Balance::class, [
        'account_id' => $this->account->id,
        'recorded_until' => today()->subDays(30)->endOfMonth()->toDateString(),
        'balance' => 300,
    ]);
    assertDatabaseHas(Balance::class, [
        'account_id' => $this->account->id,
        'recorded_until' => today()->subDays(60)->endOfMonth()->toDateString(),
        'balance' => 300,
    ]);

    $this->incomeService->deleteIncome($income);

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
