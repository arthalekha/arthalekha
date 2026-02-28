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
        ->assertSee('Profile Information')
        ->assertSee('Daily Transaction Reminder');
});

test('profile page shows user name and email', function () {
    $user = User::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertSuccessful()
        ->assertSee('John Doe')
        ->assertSee('john@example.com');
});

test('guest cannot access profile page', function () {
    $this->get(route('profile.edit'))
        ->assertRedirect(route('login'));
});

test('user can update profile information', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->put(route('user-profile-information.update'), [
            'name' => 'Updated Name',
            'email' => $user->email,
        ])
        ->assertRedirect(route('profile.edit'));

    $user->refresh();
    expect($user->name)->toBe('Updated Name');
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
