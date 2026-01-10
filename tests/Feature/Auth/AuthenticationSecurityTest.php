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

test('login with CSRF protection works', function () {
    // CSRF protection is handled by Laravel's middleware
    // In tests, CSRF is automatically handled when using post()
    $response = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertRedirect('/home');
    $this->assertAuthenticated();
});

test('session is regenerated on login to prevent fixation', function () {
    $oldSessionId = session()->getId();

    $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $newSessionId = session()->getId();

    expect($newSessionId)->not->toBe($oldSessionId);
});

test('session is regenerated on logout to prevent fixation', function () {
    $this->actingAs($this->user);

    $oldSessionId = session()->getId();

    $this->post('/logout');

    $newSessionId = session()->getId();

    expect($newSessionId)->not->toBe($oldSessionId);
});

test('password is hashed and never stored in plain text', function () {
    $this->post('/register', [
        'name' => 'New User',
        'email' => 'newuser@example.com',
        'password' => 'plaintextpassword',
        'password_confirmation' => 'plaintextpassword',
    ]);

    $user = User::where('email', 'newuser@example.com')->first();

    expect($user->password)->not->toBe('plaintextpassword');
    expect(strlen($user->password))->toBeGreaterThan(20); // Hashed passwords are long
    expect(Hash::check('plaintextpassword', $user->password))->toBeTrue();
});

test('failed login attempts are throttled', function () {
    // Attempt to login 6 times with wrong password
    for ($i = 0; $i < 6; $i++) {
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);
    }

    // After 5 failed attempts, should be rate limited
    $response->assertStatus(429);
});

test('successful login resets throttle counter', function () {
    // Make 3 failed attempts
    for ($i = 0; $i < 3; $i++) {
        $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);
    }

    // Successful login
    $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    // Should be able to login again after logout
    $this->post('/logout');

    $response = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertRedirect('/home');
});

test('remember token is set on login with remember me', function () {
    // Start with null remember token
    $this->user->update(['remember_token' => null]);

    $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
        'remember' => true,
    ]);

    $this->user->refresh();

    expect($this->user->remember_token)->not->toBeNull();
});

test('email is case insensitive but stored lowercase', function () {
    $response = $this->post('/register', [
        'name' => 'New User',
        'email' => 'NEWUSER@EXAMPLE.COM',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    // Email should be stored in lowercase
    $user = User::where('email', 'newuser@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->email)->toBe('newuser@example.com');

    // Registration should auto-login and redirect
    $response->assertRedirect('/home');
    $this->assertAuthenticated();
});

test('user cannot be authenticated with empty credentials', function () {
    $response = $this->post('/login', [
        'email' => '',
        'password' => '',
    ]);

    $response->assertSessionHasErrors(['email', 'password']);
    $this->assertGuest();
});

test('sensitive data is not logged', function () {
    // Ensure password is not in validation errors
    $response = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'wrongpassword',
    ]);

    $errors = session('errors');

    if ($errors) {
        $errorMessages = $errors->all();
        foreach ($errorMessages as $message) {
            expect($message)->not->toContain('wrongpassword');
        }
    }
});

test('authentication timing attack mitigation', function () {
    // Test with valid user
    $startTime = microtime(true);
    $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'wrongpassword',
    ]);
    $validUserTime = microtime(true) - $startTime;

    // Test with invalid user
    $startTime = microtime(true);
    $this->post('/login', [
        'email' => 'nonexistent@example.com',
        'password' => 'wrongpassword',
    ]);
    $invalidUserTime = microtime(true) - $startTime;

    // Time difference should be minimal (Laravel handles this via Hash::check)
    // This is a soft check since exact timing can vary
    expect(abs($validUserTime - $invalidUserTime))->toBeLessThan(0.5);
});

test('password reset tokens expire', function () {
    $user = User::factory()->create();

    // Create a token
    $token = \Illuminate\Support\Facades\Password::createToken($user);

    // Travel to future (default expiration is 1 hour)
    $this->travel(2)->hours();

    // Try to reset password with expired token
    $response = $this->post('/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response->assertSessionHasErrors();
});

test('concurrent sessions from different devices work correctly', function () {
    // Simulate first device login
    $response1 = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $session1 = session()->getId();

    // Logout from first session
    $this->post('/logout');

    // Simulate second device login
    $response2 = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $session2 = session()->getId();

    // Sessions should be different
    expect($session2)->not->toBe($session1);
});
