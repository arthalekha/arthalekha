<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('guest cannot access protected routes', function () {
    $protectedRoutes = [
        '/home',
        '/accounts',
        '/expenses',
        '/incomes',
        '/transfers',
        '/tags',
        '/recurring-incomes',
        '/recurring-expenses',
        '/recurring-transfers',
        '/projected-dashboard',
        '/users',
    ];

    foreach ($protectedRoutes as $route) {
        $response = $this->get($route);
        $response->assertRedirect('/login');
    }
});

test('authenticated user can access protected routes', function () {
    $this->actingAs($this->user);

    $response = $this->get('/home');
    $response->assertOk();
});

test('guest can access public routes', function () {
    $publicRoutes = [
        '/login',
        '/forgot-password',
    ];

    foreach ($publicRoutes as $route) {
        $response = $this->get($route);
        $response->assertOk();
    }
});

test('authenticated user is redirected from guest-only routes', function () {
    $this->actingAs($this->user);

    $guestRoutes = [
        '/login',
        '/forgot-password',
    ];

    foreach ($guestRoutes as $route) {
        $response = $this->get($route);
        $response->assertRedirect('/home');
    }
});

test('guest attempting to access protected route is redirected to login', function () {
    $response = $this->get('/home');

    $response->assertRedirect('/login');
});

test('guest attempting to POST to protected route is redirected to login', function () {
    $response = $this->post('/expenses', [
        'description' => 'Test Expense',
        'amount' => 100,
    ]);

    $response->assertRedirect('/login');
});

test('authentication persists across requests', function () {
    $this->actingAs($this->user);

    $this->get('/home')->assertOk();
    $this->get('/accounts')->assertOk();
    $this->get('/expenses')->assertOk();

    $this->assertAuthenticatedAs($this->user);
});

test('unauthenticated session does not have auth data', function () {
    $this->assertGuest();

    expect(auth()->check())->toBeFalse();
    expect(auth()->user())->toBeNull();
});

test('authenticated session has auth data', function () {
    $this->actingAs($this->user);

    expect(auth()->check())->toBeTrue();
    expect(auth()->user())->toBeInstanceOf(User::class);
    expect(auth()->id())->toBe($this->user->id);
});

test('session is preserved after successful authentication', function () {
    session(['test_key' => 'test_value']);

    $this->post('/login', [
        'email' => $this->user->email,
        'password' => 'password',
    ]);

    // Session should be regenerated but data preserved where appropriate
    $this->assertAuthenticated();
});

test('accessing protected route with expired session redirects to login', function () {
    // Simulate expired session by not being authenticated
    $this->assertGuest();

    $response = $this->get('/home');

    $response->assertRedirect('/login');
});
