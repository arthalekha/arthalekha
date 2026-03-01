<?php

use App\Enums\Frequency;
use App\Jobs\SendRecurringTransactionRemindersJob;
use App\Models\Account;
use App\Models\RecurringExpense;
use App\Models\RecurringIncome;
use App\Models\User;
use App\Notifications\RecurringTransactionPendingNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('sends notification to user with pending recurring income without account', function () {
    Notification::fake();

    $user = User::factory()->create();

    RecurringIncome::factory()
        ->forUser($user)
        ->withoutAccount()
        ->create([
            'next_transaction_at' => now()->subDay(),
            'frequency' => Frequency::Monthly,
        ]);

    SendRecurringTransactionRemindersJob::dispatch();

    Notification::assertSentTo($user, RecurringTransactionPendingNotification::class);
});

test('sends notification to user with pending recurring expense without account', function () {
    Notification::fake();

    $user = User::factory()->create();

    RecurringExpense::factory()
        ->forUser($user)
        ->withoutAccount()
        ->create([
            'next_transaction_at' => now()->subDay(),
            'frequency' => Frequency::Monthly,
        ]);

    SendRecurringTransactionRemindersJob::dispatch();

    Notification::assertSentTo($user, RecurringTransactionPendingNotification::class);
});

test('does not send notification to user with no pending items', function () {
    Notification::fake();

    $user = User::factory()->create();

    SendRecurringTransactionRemindersJob::dispatch();

    Notification::assertNotSentTo($user, RecurringTransactionPendingNotification::class);
});

test('does not send notification when recurring items have accounts', function () {
    Notification::fake();

    $user = User::factory()->create();
    $account = Account::factory()->forUser($user)->create();

    RecurringIncome::factory()
        ->forUser($user)
        ->forAccount($account)
        ->create([
            'next_transaction_at' => now()->subDay(),
            'frequency' => Frequency::Monthly,
        ]);

    SendRecurringTransactionRemindersJob::dispatch();

    Notification::assertNotSentTo($user, RecurringTransactionPendingNotification::class);
});

test('does not send notification when recurring items are not yet due', function () {
    Notification::fake();

    $user = User::factory()->create();

    RecurringIncome::factory()
        ->forUser($user)
        ->withoutAccount()
        ->create([
            'next_transaction_at' => now()->addDay(),
            'frequency' => Frequency::Monthly,
        ]);

    SendRecurringTransactionRemindersJob::dispatch();

    Notification::assertNotSentTo($user, RecurringTransactionPendingNotification::class);
});
