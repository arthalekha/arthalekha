<?php

use App\Features\DailyTransactionReminder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;

uses(RefreshDatabase::class);

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertSuccessful()
        ->assertSee('Profile Settings')
        ->assertSee('Daily Transaction Reminder');
});

test('guest cannot access profile page', function () {
    $this->get(route('profile.edit'))
        ->assertRedirect(route('login'));
});

test('user can enable daily transaction reminder', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put(route('profile.update'), [
            'daily_transaction_reminder' => 1,
        ])
        ->assertRedirect(route('profile.edit'));

    expect(Feature::for($user)->active(DailyTransactionReminder::class))->toBeTrue();
});

test('user can disable daily transaction reminder', function () {
    $user = User::factory()->create();
    Feature::for($user)->activate(DailyTransactionReminder::class);

    $this->actingAs($user)
        ->put(route('profile.update'), [
            'daily_transaction_reminder' => 0,
        ])
        ->assertRedirect(route('profile.edit'));

    expect(Feature::for($user)->active(DailyTransactionReminder::class))->toBeFalse();
});

test('profile page shows correct toggle state when feature is enabled', function () {
    $user = User::factory()->create();
    Feature::for($user)->activate(DailyTransactionReminder::class);

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertSuccessful()
        ->assertSee('checked');
});
