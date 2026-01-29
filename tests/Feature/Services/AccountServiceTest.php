<?php

use App\Models\Account;
use App\Models\Balance;
use App\Models\User;
use App\Services\AccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\assertDatabaseCount;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('tests balance entry insertions', function (int $days, int $count) {
    Date::setTestNow(now()->setDay(15));
    $service = app(AccountService::class);

    $this->user = User::factory()->create();

    $account = Account::factory()->make([
        'initial_date' => today()->subDays($days),
    ])->toArray();

    $service->createAccount($this->user, $account);

    assertDatabaseCount(Balance::class, $count);
})->with([
    'today has 0 balance count' => [0, 0],
    'yesterday has 0 balance count' => [1, 0],
    'last month has 1 balance count' => [30, 1],
    'last 3 months has 3 balance count' => [90, 3],
]);

it('restores a trashed account', function () {
    $service = app(AccountService::class);

    $account = Account::factory()->forUser($this->user)->create();
    $account->delete();

    expect(Account::find($account->id))->toBeNull();

    $service->restoreAccount($account);

    expect(Account::find($account->id))->not->toBeNull()
        ->and(Account::find($account->id)->deleted_at)->toBeNull();
});
