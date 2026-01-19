<?php

use App\Models\Account;
use App\Models\Balance;
use App\Models\Transfer;
use App\Models\User;
use App\Services\AccountService;
use App\Services\TransferService;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    Date::setTestNow(now()->setDay(15));

    $a1Data = Account::factory()->for($this->user)->ofSameBalances(100)->make([
        'initial_date' => today()->subDays(60),
    ]);
    $this->a1 = app(AccountService::class)->createAccount($this->user, $a1Data->toArray());

    $a2Data = Account::factory()->for($this->user)->ofSameBalances(1000)->make([
        'initial_date' => today()->subDays(60),
    ]);
    $this->a2 = app(AccountService::class)->createAccount($this->user, $a2Data->toArray());

    $this->transferService = app(TransferService::class);
});

it('updates current balance when transfer is created, updated and deleted', function () {
    $transferData = Transfer::factory()
        ->for($this->user)
        ->make([
            'creditor_id' => $this->a1->id,
            'debtor_id' => $this->a2->id,
            'amount' => 100,
            'transacted_at' => today()->subDays(30),
        ]);

    $transfer = $this->transferService->createTransfer($this->user, $transferData->toArray());

    assertDatabaseHas(Balance::class, [
        'account_id' => $this->a1->id,
        'recorded_until' => today()->subDays(30)->endOfMonth()->toDateString(),
        'balance' => 200,
    ]);
    assertDatabaseHas(Balance::class, [
        'account_id' => $this->a1->id,
        'recorded_until' => today()->subDays(60)->endOfMonth()->toDateString(),
        'balance' => 100,
    ]);

    assertDatabaseHas(Balance::class, [
        'account_id' => $this->a2->id,
        'recorded_until' => today()->subDays(30)->endOfMonth()->toDateString(),
        'balance' => 900,
    ]);
    assertDatabaseHas(Balance::class, [
        'account_id' => $this->a2->id,
        'recorded_until' => today()->subDays(60)->endOfMonth()->toDateString(),
        'balance' => 1000,
    ]);

    $data = [
        'amount' => 200,
        'transacted_at' => today()->subDays(60),
    ];

    $transfer = $this->transferService->updateTransfer($transfer, $data);

    assertDatabaseHas(Balance::class, [
        'account_id' => $this->a1->id,
        'recorded_until' => today()->subDays(30)->endOfMonth()->toDateString(),
        'balance' => 300,
    ]);
    assertDatabaseHas(Balance::class, [
        'account_id' => $this->a1->id,
        'recorded_until' => today()->subDays(60)->endOfMonth()->toDateString(),
        'balance' => 300,
    ]);

    assertDatabaseHas(Balance::class, [
        'account_id' => $this->a2->id,
        'recorded_until' => today()->subDays(30)->endOfMonth()->toDateString(),
        'balance' => 800,
    ]);
    assertDatabaseHas(Balance::class, [
        'account_id' => $this->a2->id,
        'recorded_until' => today()->subDays(60)->endOfMonth()->toDateString(),
        'balance' => 800,
    ]);

    $data = [
        'debtor_id' => $transfer->creditor_id,
        'creditor_id' => $transfer->debtor_id,
    ];

    $transfer = $this->transferService->updateTransfer($transfer, $data);

    assertDatabaseHas(Balance::class, [
        'account_id' => $this->a1->id,
        'recorded_until' => today()->subDays(30)->endOfMonth()->toDateString(),
        'balance' => -100,
    ]);
    assertDatabaseHas(Balance::class, [
        'account_id' => $this->a1->id,
        'recorded_until' => today()->subDays(60)->endOfMonth()->toDateString(),
        'balance' => -100,
    ]);

    assertDatabaseHas(Balance::class, [
        'account_id' => $this->a2->id,
        'recorded_until' => today()->subDays(30)->endOfMonth()->toDateString(),
        'balance' => 1200,
    ]);
    assertDatabaseHas(Balance::class, [
        'account_id' => $this->a2->id,
        'recorded_until' => today()->subDays(60)->endOfMonth()->toDateString(),
        'balance' => 1200,
    ]);

    $this->transferService->deleteTransfer($transfer);

    assertDatabaseHas(Balance::class, [
        'account_id' => $this->a1->id,
        'recorded_until' => today()->subDays(30)->endOfMonth()->toDateString(),
        'balance' => 100,
    ]);
    assertDatabaseHas(Balance::class, [
        'account_id' => $this->a1->id,
        'recorded_until' => today()->subDays(60)->endOfMonth()->toDateString(),
        'balance' => 100,
    ]);
    assertDatabaseHas(Balance::class, [
        'account_id' => $this->a2->id,
        'recorded_until' => today()->subDays(30)->endOfMonth()->toDateString(),
        'balance' => 1000,
    ]);
    assertDatabaseHas(Balance::class, [
        'account_id' => $this->a2->id,
        'recorded_until' => today()->subDays(60)->endOfMonth()->toDateString(),
        'balance' => 1000,
    ]);
});
