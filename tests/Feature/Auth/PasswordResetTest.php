<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('oldpassword'),
    ]);
});

test('user can view forgot password page', function () {
    $response = $this->get('/forgot-password');

    $response->assertOk();
    $response->assertViewIs('auth.forgot-password');
});

test('authenticated user cannot view forgot password page', function () {
    $response = $this->actingAs($this->user)->get('/forgot-password');

    $response->assertRedirect('/home');
});

test('user can request password reset link', function () {
    Notification::fake();

    $response = $this->post('/forgot-password', [
        'email' => 'test@example.com',
    ]);

    $response->assertSessionHasNoErrors();

    Notification::assertSentTo($this->user, ResetPassword::class);
});

test('password reset link request requires email', function () {
    $response = $this->post('/forgot-password', [
        'email' => '',
    ]);

    $response->assertSessionHasErrors('email');
});

test('password reset link request requires valid email format', function () {
    $response = $this->post('/forgot-password', [
        'email' => 'not-an-email',
    ]);

    $response->assertSessionHasErrors('email');
});

test('password reset link request with non-existent email shows error', function () {
    Notification::fake();

    $response = $this->post('/forgot-password', [
        'email' => 'nonexistent@example.com',
    ]);

    // Fortify's default behavior is to show an error for non-existent emails
    $response->assertSessionHasErrors();

    // No notification should be sent
    Notification::assertNothingSent();
});

test('user can view password reset page with valid token', function () {
    $token = Password::createToken($this->user);

    $response = $this->get("/reset-password/{$token}?email=test@example.com");

    $response->assertOk();
    $response->assertViewIs('auth.reset-password');
});

test('user can reset password with valid token', function () {
    $token = Password::createToken($this->user);

    $response = $this->post('/reset-password', [
        'token' => $token,
        'email' => 'test@example.com',
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect('/login');

    // Verify password was changed
    $this->user->refresh();
    expect(Hash::check('newpassword123', $this->user->password))->toBeTrue();
    expect(Hash::check('oldpassword', $this->user->password))->toBeFalse();
});

test('user can login with new password after reset', function () {
    $token = Password::createToken($this->user);

    $this->post('/reset-password', [
        'token' => $token,
        'email' => 'test@example.com',
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'newpassword123',
    ]);

    $response->assertRedirect('/home');
    $this->assertAuthenticated();
});

test('password reset requires valid token', function () {
    $response = $this->post('/reset-password', [
        'token' => 'invalid-token',
        'email' => 'test@example.com',
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response->assertSessionHasErrors();

    // Verify password was NOT changed
    $this->user->refresh();
    expect(Hash::check('oldpassword', $this->user->password))->toBeTrue();
});

test('password reset requires matching email', function () {
    $token = Password::createToken($this->user);

    $response = $this->post('/reset-password', [
        'token' => $token,
        'email' => 'wrong@example.com',
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response->assertSessionHasErrors();

    // Verify password was NOT changed
    $this->user->refresh();
    expect(Hash::check('oldpassword', $this->user->password))->toBeTrue();
});

test('password reset requires password', function () {
    $token = Password::createToken($this->user);

    $response = $this->post('/reset-password', [
        'token' => $token,
        'email' => 'test@example.com',
        'password' => '',
        'password_confirmation' => '',
    ]);

    $response->assertSessionHasErrors('password');
});

test('password reset requires password confirmation', function () {
    $token = Password::createToken($this->user);

    $response = $this->post('/reset-password', [
        'token' => $token,
        'email' => 'test@example.com',
        'password' => 'newpassword123',
        'password_confirmation' => '',
    ]);

    $response->assertSessionHasErrors('password');
});

test('password reset requires matching password confirmation', function () {
    $token = Password::createToken($this->user);

    $response = $this->post('/reset-password', [
        'token' => $token,
        'email' => 'test@example.com',
        'password' => 'newpassword123',
        'password_confirmation' => 'differentpassword',
    ]);

    $response->assertSessionHasErrors('password');
});

test('password reset requires minimum password length', function () {
    $token = Password::createToken($this->user);

    $response = $this->post('/reset-password', [
        'token' => $token,
        'email' => 'test@example.com',
        'password' => 'short',
        'password_confirmation' => 'short',
    ]);

    $response->assertSessionHasErrors('password');
});

test('password reset token can only be used once', function () {
    $token = Password::createToken($this->user);

    // First reset succeeds
    $this->post('/reset-password', [
        'token' => $token,
        'email' => 'test@example.com',
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    // Second attempt with same token fails
    $response = $this->post('/reset-password', [
        'token' => $token,
        'email' => 'test@example.com',
        'password' => 'anotherpassword',
        'password_confirmation' => 'anotherpassword',
    ]);

    $response->assertSessionHasErrors();

    // Verify password is still newpassword123, not anotherpassword
    $this->user->refresh();
    expect(Hash::check('newpassword123', $this->user->password))->toBeTrue();
});

test('user can login with new password after password reset', function () {
    $token = Password::createToken($this->user);

    // Reset password (user is not logged in)
    $this->post('/reset-password', [
        'token' => $token,
        'email' => 'test@example.com',
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    // Verify old password no longer works
    $response = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'oldpassword',
    ]);

    $response->assertSessionHasErrors();
    $this->assertGuest();

    // Verify new password works
    $response = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'newpassword123',
    ]);

    $response->assertRedirect('/home');
    $this->assertAuthenticated();
});

test('password reset works with exact email match', function () {
    $token = Password::createToken($this->user);

    // Must use the exact email as stored in database
    $response = $this->post('/reset-password', [
        'token' => $token,
        'email' => 'test@example.com',
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response->assertSessionHasNoErrors();

    // Verify password was changed
    $this->user->refresh();
    expect(Hash::check('newpassword123', $this->user->password))->toBeTrue();
});
