<?php

use App\Models\Account;
use App\Models\Expense;
use App\Models\Income;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->account = Account::factory()->create(['user_id' => $this->user->id]);
});

test('home page requires authentication', function () {
    $response = $this->get(route('home'));

    $response->assertRedirect(route('login'));
});

test('authenticated user can view home page', function () {
    $response = $this->actingAs($this->user)->get(route('home'));

    $response->assertOk();
    $response->assertViewIs('home');
});

test('home page displays current month by default', function () {
    $currentMonth = Carbon::now()->format('F Y');

    $response = $this->actingAs($this->user)->get(route('home'));

    $response->assertOk();
    $response->assertSee($currentMonth);
});

test('home page shows income and expense totals for current month', function () {
    $now = Carbon::now();

    Income::factory()->create([
        'user_id' => $this->user->id,
        'account_id' => $this->account->id,
        'amount' => 1000,
        'transacted_at' => $now,
    ]);

    Income::factory()->create([
        'user_id' => $this->user->id,
        'account_id' => $this->account->id,
        'amount' => 500,
        'transacted_at' => $now,
    ]);

    Expense::factory()->create([
        'user_id' => $this->user->id,
        'account_id' => $this->account->id,
        'amount' => 300,
        'transacted_at' => $now,
    ]);

    $response = $this->actingAs($this->user)->get(route('home'));

    $response->assertOk();
    $response->assertViewHas('totalIncome', 1500.0);
    $response->assertViewHas('totalExpense', 300.0);
    $response->assertViewHas('netSavings', 1200.0);
});

test('home page can filter by month', function () {
    $pastMonth = Carbon::now()->subMonth();

    Income::factory()->create([
        'user_id' => $this->user->id,
        'account_id' => $this->account->id,
        'amount' => 2000,
        'transacted_at' => $pastMonth,
    ]);

    Expense::factory()->create([
        'user_id' => $this->user->id,
        'account_id' => $this->account->id,
        'amount' => 800,
        'transacted_at' => $pastMonth,
    ]);

    $response = $this->actingAs($this->user)->get(route('home', [
        'month' => $pastMonth->format('Y-m'),
    ]));

    $response->assertOk();
    $response->assertViewHas('totalIncome', 2000.0);
    $response->assertViewHas('totalExpense', 800.0);
    $response->assertViewHas('netSavings', 1200.0);
    $response->assertSee($pastMonth->format('F Y'));
});

test('home page only shows data for authenticated user', function () {
    $otherUser = User::factory()->create();
    $otherAccount = Account::factory()->create(['user_id' => $otherUser->id]);

    Income::factory()->create([
        'user_id' => $otherUser->id,
        'account_id' => $otherAccount->id,
        'amount' => 5000,
        'transacted_at' => Carbon::now(),
    ]);

    Income::factory()->create([
        'user_id' => $this->user->id,
        'account_id' => $this->account->id,
        'amount' => 1000,
        'transacted_at' => Carbon::now(),
    ]);

    $response = $this->actingAs($this->user)->get(route('home'));

    $response->assertOk();
    $response->assertViewHas('totalIncome', 1000.0);
});

test('home page provides chart data arrays', function () {
    $response = $this->actingAs($this->user)->get(route('home'));

    $response->assertOk();
    $response->assertViewHas('days');
    $response->assertViewHas('incomeData');
    $response->assertViewHas('expenseData');

    $days = $response->viewData('days');
    $incomeData = $response->viewData('incomeData');
    $expenseData = $response->viewData('expenseData');

    expect($days)->toBeArray();
    expect($incomeData)->toBeArray();
    expect($expenseData)->toBeArray();
    expect(count($days))->toBeGreaterThanOrEqual(28);
    expect(count($incomeData))->toBe(count($days));
    expect(count($expenseData))->toBe(count($days));
});
