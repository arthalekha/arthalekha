<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('init command creates first user', function () {
    $this->artisan('app:init')
        ->expectsQuestion('What is your name?', 'John Doe')
        ->expectsQuestion('What is your email?', 'john@example.com')
        ->expectsQuestion('Create a password', 'password123')
        ->assertSuccessful();

    $this->assertDatabaseCount('users', 1);
    $this->assertDatabaseHas('users', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);
});

test('init command fails when user already exists', function () {
    User::factory()->create();

    $this->artisan('app:init')
        ->assertFailed();

    $this->assertDatabaseCount('users', 1);
});
