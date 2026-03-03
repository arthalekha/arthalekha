<?php

use App\Enums\Frequency;
use App\Models\Account;
use App\Models\Expense;
use App\Models\Income;
use App\Models\RecurringExpense;
use App\Models\RecurringIncome;
use App\Models\User;
use Database\Factories\TagFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->account = Account::factory()->forUser($this->user)->create();
    $this->actingAs($this->user);
});

test('record income creates an income with selected account', function () {
    $recurringIncome = RecurringIncome::factory()
        ->forUser($this->user)
        ->withoutAccount()
        ->create([
            'next_transaction_at' => now()->subDay(),
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => null,
        ]);

    $transactedAt = now()->format('Y-m-d\TH:i');

    $response = $this->post(route('recurring-incomes.record', $recurringIncome), [
        'account_id' => $this->account->id,
        'transacted_at' => $transactedAt,
    ]);

    $response->assertRedirect(route('recurring-transactions.dashboard'));
    $response->assertSessionHas('success');

    expect(Income::where('user_id', $this->user->id)->count())->toBe(1);

    $income = Income::where('user_id', $this->user->id)->first();
    expect($income->account_id)->toBe($this->account->id);
    expect($income->description)->toBe($recurringIncome->description);
    expect($income->amount)->toBe($recurringIncome->amount);
    expect($income->transacted_at->format('Y-m-d H:i'))->toBe(now()->format('Y-m-d H:i'));
});

test('record income syncs tags', function () {
    $tag = TagFactory::new()->create();

    $recurringIncome = RecurringIncome::factory()
        ->forUser($this->user)
        ->withoutAccount()
        ->create([
            'next_transaction_at' => now()->subDay(),
            'frequency' => Frequency::Monthly,
        ]);

    $recurringIncome->tags()->sync([$tag->id]);

    $this->post(route('recurring-incomes.record', $recurringIncome), [
        'account_id' => $this->account->id,
        'transacted_at' => now()->format('Y-m-d\TH:i'),
    ]);

    $income = Income::where('user_id', $this->user->id)->first();
    expect($income->tags)->toHaveCount(1);
    expect($income->tags->first()->id)->toBe($tag->id);
});

test('record income advances next_transaction_at', function () {
    $originalDate = now()->subDay();

    $recurringIncome = RecurringIncome::factory()
        ->forUser($this->user)
        ->withoutAccount()
        ->create([
            'next_transaction_at' => $originalDate,
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => null,
        ]);

    $this->post(route('recurring-incomes.record', $recurringIncome), [
        'account_id' => $this->account->id,
        'transacted_at' => now()->format('Y-m-d\TH:i'),
    ]);

    $recurringIncome->refresh();

    expect($recurringIncome->next_transaction_at->format('Y-m-d'))
        ->toBe($originalDate->copy()->addMonth()->format('Y-m-d'));
});

test('record income deletes recurring when recurrences exhausted', function () {
    $recurringIncome = RecurringIncome::factory()
        ->forUser($this->user)
        ->withoutAccount()
        ->create([
            'next_transaction_at' => now()->subDay(),
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => 1,
        ]);

    $this->post(route('recurring-incomes.record', $recurringIncome), [
        'account_id' => $this->account->id,
        'transacted_at' => now()->format('Y-m-d\TH:i'),
    ]);

    expect(RecurringIncome::find($recurringIncome->id))->toBeNull();
    expect(Income::where('user_id', $this->user->id)->count())->toBe(1);
});

test('record income requires account_id', function () {
    $recurringIncome = RecurringIncome::factory()
        ->forUser($this->user)
        ->withoutAccount()
        ->create([
            'next_transaction_at' => now()->subDay(),
            'frequency' => Frequency::Monthly,
        ]);

    $response = $this->post(route('recurring-incomes.record', $recurringIncome), [
        'transacted_at' => now()->format('Y-m-d\TH:i'),
    ]);

    $response->assertSessionHasErrors('account_id');
    expect(Income::where('user_id', $this->user->id)->count())->toBe(0);
});

test('record income requires transacted_at', function () {
    $recurringIncome = RecurringIncome::factory()
        ->forUser($this->user)
        ->withoutAccount()
        ->create([
            'next_transaction_at' => now()->subDay(),
            'frequency' => Frequency::Monthly,
        ]);

    $response = $this->post(route('recurring-incomes.record', $recurringIncome), [
        'account_id' => $this->account->id,
    ]);

    $response->assertSessionHasErrors('transacted_at');
    expect(Income::where('user_id', $this->user->id)->count())->toBe(0);
});

test('record income rejects account from another user', function () {
    $otherUser = User::factory()->create();
    $otherAccount = Account::factory()->forUser($otherUser)->create();

    $recurringIncome = RecurringIncome::factory()
        ->forUser($this->user)
        ->withoutAccount()
        ->create([
            'next_transaction_at' => now()->subDay(),
            'frequency' => Frequency::Monthly,
        ]);

    $response = $this->post(route('recurring-incomes.record', $recurringIncome), [
        'account_id' => $otherAccount->id,
        'transacted_at' => now()->format('Y-m-d\TH:i'),
    ]);

    $response->assertSessionHasErrors('account_id');
    expect(Income::where('user_id', $this->user->id)->count())->toBe(0);
});

test('record expense creates an expense with selected account', function () {
    $recurringExpense = RecurringExpense::factory()
        ->forUser($this->user)
        ->withoutAccount()
        ->create([
            'next_transaction_at' => now()->subDay(),
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => null,
        ]);

    $transactedAt = now()->format('Y-m-d\TH:i');

    $response = $this->post(route('recurring-expenses.record', $recurringExpense), [
        'account_id' => $this->account->id,
        'transacted_at' => $transactedAt,
    ]);

    $response->assertRedirect(route('recurring-transactions.dashboard'));
    $response->assertSessionHas('success');

    expect(Expense::where('user_id', $this->user->id)->count())->toBe(1);

    $expense = Expense::where('user_id', $this->user->id)->first();
    expect($expense->account_id)->toBe($this->account->id);
    expect($expense->description)->toBe($recurringExpense->description);
    expect($expense->transacted_at->format('Y-m-d H:i'))->toBe(now()->format('Y-m-d H:i'));
});

test('record expense advances next_transaction_at', function () {
    $originalDate = now()->subDay();

    $recurringExpense = RecurringExpense::factory()
        ->forUser($this->user)
        ->withoutAccount()
        ->create([
            'next_transaction_at' => $originalDate,
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => null,
        ]);

    $this->post(route('recurring-expenses.record', $recurringExpense), [
        'account_id' => $this->account->id,
        'transacted_at' => now()->format('Y-m-d\TH:i'),
    ]);

    $recurringExpense->refresh();

    expect($recurringExpense->next_transaction_at->format('Y-m-d'))
        ->toBe($originalDate->copy()->addMonth()->format('Y-m-d'));
});

test('record expense deletes recurring when recurrences exhausted', function () {
    $recurringExpense = RecurringExpense::factory()
        ->forUser($this->user)
        ->withoutAccount()
        ->create([
            'next_transaction_at' => now()->subDay(),
            'frequency' => Frequency::Monthly,
            'remaining_recurrences' => 1,
        ]);

    $this->post(route('recurring-expenses.record', $recurringExpense), [
        'account_id' => $this->account->id,
        'transacted_at' => now()->format('Y-m-d\TH:i'),
    ]);

    expect(RecurringExpense::find($recurringExpense->id))->toBeNull();
    expect(Expense::where('user_id', $this->user->id)->count())->toBe(1);
});
