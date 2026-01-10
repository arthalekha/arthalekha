<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
    ]);
});

test('user can view login page', function () {
    $response = $this->get('/login');

    $response->assertOk();
    $response->assertViewIs('auth.login');
});

test('authenticated user cannot view login page', function () {
    $response = $this->actingAs($this->user)->get('/login');

    $response->assertRedirect('/home');
});

test('user can login with valid credentials', function () {
    $response = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertRedirect('/home');
    $this->assertAuthenticatedAs($this->user);
});

test('user cannot login with invalid email', function () {
    $response = $this->post('/login', [
        'email' => 'wrong@example.com',
        'password' => 'password123',
    ]);

    $response->assertSessionHasErrors();
    $this->assertGuest();
});

test('user cannot login with invalid password', function () {
    $response = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertSessionHasErrors();
    $this->assertGuest();
});

test('user cannot login with empty email', function () {
    $response = $this->post('/login', [
        'email' => '',
        'password' => 'password123',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('user cannot login with empty password', function () {
    $response = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => '',
    ]);

    $response->assertSessionHasErrors('password');
    $this->assertGuest();
});

test('user cannot login with invalid email format', function () {
    $response = $this->post('/login', [
        'email' => 'not-an-email',
        'password' => 'password123',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('login is case insensitive for email', function () {
    $response = $this->post('/login', [
        'email' => 'TEST@EXAMPLE.COM',
        'password' => 'password123',
    ]);

    $response->assertRedirect('/home');
    $this->assertAuthenticated();
});

test('user session is regenerated after login', function () {
    $oldSession = session()->getId();

    $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    expect(session()->getId())->not->toBe($oldSession);
});

test('login attempts are rate limited', function () {
    // Make 6 failed login attempts (rate limit is 5 per minute)
    for ($i = 0; $i < 6; $i++) {
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);
    }

    // The 6th attempt should be rate limited
    $response->assertStatus(429); // Too Many Requests
});

test('remember me functionality works', function () {
    $response = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
        'remember' => true,
    ]);

    $response->assertRedirect('/home');
    $this->assertAuthenticatedAs($this->user);

    // Check that remember token is set
    $this->user->refresh();
    expect($this->user->remember_token)->not->toBeNull();
});

test('user can login without remember me', function () {
    $response = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
        'remember' => false,
    ]);

    $response->assertRedirect('/home');
    $this->assertAuthenticatedAs($this->user);
});
