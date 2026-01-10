<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('authenticated user can logout', function () {
    $response = $this->actingAs($this->user)->post('/logout');

    $response->assertRedirect('/');
    $this->assertGuest();
});

test('guest cannot logout', function () {
    $response = $this->post('/logout');

    $response->assertRedirect('/login');
    $this->assertGuest();
});

test('logout clears user session', function () {
    $this->actingAs($this->user);

    session(['test_key' => 'test_value']);
    expect(session('test_key'))->toBe('test_value');

    $this->post('/logout');

    expect(session('test_key'))->toBeNull();
});

test('logout invalidates authentication', function () {
    $this->actingAs($this->user);
    $this->assertAuthenticated();

    $this->post('/logout');

    $this->assertGuest();
});

test('user cannot access protected routes after logout', function () {
    $this->actingAs($this->user);

    $this->post('/logout');

    $response = $this->get('/home');
    $response->assertRedirect('/login');
});

test('logout regenerates session id to prevent fixation', function () {
    $this->actingAs($this->user);

    $oldSessionId = session()->getId();

    $this->post('/logout');

    expect(session()->getId())->not->toBe($oldSessionId);
});
