<?php

use App\Features\DailyTransactionReminder;
use App\Jobs\SendDailyTransactionRemindersJob;
use App\Models\User;
use App\Notifications\DailyTransactionReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Pennant\Feature;

uses(RefreshDatabase::class);

test('daily transaction reminder feature defaults to false', function () {
    $user = User::factory()->create();

    expect(Feature::for($user)->active(DailyTransactionReminder::class))->toBeFalse();
});

test('daily transaction reminder feature can be activated for a user', function () {
    $user = User::factory()->create();

    Feature::for($user)->activate(DailyTransactionReminder::class);

    expect(Feature::for($user)->active(DailyTransactionReminder::class))->toBeTrue();
});

test('job sends notification to users with feature enabled', function () {
    Notification::fake();

    $optedInUser = User::factory()->create();
    $optedOutUser = User::factory()->create();

    Feature::for($optedInUser)->activate(DailyTransactionReminder::class);

    (new SendDailyTransactionRemindersJob)->handle();

    Notification::assertSentTo($optedInUser, DailyTransactionReminderNotification::class);
    Notification::assertNotSentTo($optedOutUser, DailyTransactionReminderNotification::class);
});

test('job sends notification to multiple opted-in users', function () {
    Notification::fake();

    $optedInUsers = User::factory()->count(3)->create();
    $optedOutUser = User::factory()->create();

    $optedInUsers->each(function (User $user) {
        Feature::for($user)->activate(DailyTransactionReminder::class);
    });

    (new SendDailyTransactionRemindersJob)->handle();

    $optedInUsers->each(function (User $user) {
        Notification::assertSentTo($user, DailyTransactionReminderNotification::class);
    });

    Notification::assertNotSentTo($optedOutUser, DailyTransactionReminderNotification::class);
});

test('notification is sent via mail channel', function () {
    Notification::fake();

    $user = User::factory()->create();
    Feature::for($user)->activate(DailyTransactionReminder::class);

    (new SendDailyTransactionRemindersJob)->handle();

    Notification::assertSentTo($user, DailyTransactionReminderNotification::class, function ($notification, $channels) {
        return $channels === ['mail'];
    });
});

test('notification mail contains expected content', function () {
    $user = User::factory()->create();

    $notification = new DailyTransactionReminderNotification;
    $mail = $notification->toMail($user);

    expect($mail->subject)->toBe('Daily Transaction Reminder')
        ->and($mail->introLines)->toContain("Don't forget to log your transactions for today!");
});
