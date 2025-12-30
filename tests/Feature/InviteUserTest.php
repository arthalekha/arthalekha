<?php

use App\Mail\UserInvitedMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('invite user page requires authentication', function () {
    $response = $this->get(route('users.invite'));

    $response->assertRedirect(route('login'));
});

test('authenticated user can view invite user page', function () {
    $response = $this->actingAs($this->user)->get(route('users.invite'));

    $response->assertOk();
    $response->assertViewIs('users.invite');
});

test('authenticated user can invite a new user', function () {
    Mail::fake();

    $response = $this->actingAs($this->user)->post(route('users.invite.store'), [
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    $response->assertRedirect(route('users.invite'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('users', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    Mail::assertSent(UserInvitedMail::class, function ($mail) {
        return $mail->hasTo('john@example.com');
    });
});

test('invited user receives email with password', function () {
    Mail::fake();

    $this->actingAs($this->user)->post(route('users.invite.store'), [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ]);

    Mail::assertSent(UserInvitedMail::class, function ($mail) {
        return $mail->user->email === 'jane@example.com'
            && strlen($mail->password) === 12;
    });
});

test('inviting a user requires a name', function () {
    $response = $this->actingAs($this->user)->post(route('users.invite.store'), [
        'email' => 'john@example.com',
    ]);

    $response->assertSessionHasErrors('name');
});

test('inviting a user requires an email', function () {
    $response = $this->actingAs($this->user)->post(route('users.invite.store'), [
        'name' => 'John Doe',
    ]);

    $response->assertSessionHasErrors('email');
});

test('inviting a user requires a valid email', function () {
    $response = $this->actingAs($this->user)->post(route('users.invite.store'), [
        'name' => 'John Doe',
        'email' => 'invalid-email',
    ]);

    $response->assertSessionHasErrors('email');
});

test('cannot invite a user with an existing email', function () {
    $existingUser = User::factory()->create(['email' => 'existing@example.com']);

    $response = $this->actingAs($this->user)->post(route('users.invite.store'), [
        'name' => 'John Doe',
        'email' => 'existing@example.com',
    ]);

    $response->assertSessionHasErrors('email');
});

test('invited user can login with the generated password', function () {
    Mail::fake();

    $this->actingAs($this->user)->post(route('users.invite.store'), [
        'name' => 'New User',
        'email' => 'newuser@example.com',
    ]);

    $capturedPassword = null;
    Mail::assertSent(UserInvitedMail::class, function ($mail) use (&$capturedPassword) {
        $capturedPassword = $mail->password;

        return true;
    });

    auth()->logout();

    $response = $this->post(route('login'), [
        'email' => 'newuser@example.com',
        'password' => $capturedPassword,
    ]);

    $response->assertRedirect(route('home', absolute: false));
    $this->assertAuthenticatedAs(User::where('email', 'newuser@example.com')->first());
});

test('user list page requires authentication', function () {
    $response = $this->get(route('users.index'));

    $response->assertRedirect(route('login'));
});

test('authenticated user can view user list page', function () {
    $response = $this->actingAs($this->user)->get(route('users.index'));

    $response->assertOk();
    $response->assertViewIs('users.index');
    $response->assertViewHas('users');
});

test('user list page shows all users', function () {
    $users = User::factory()->count(3)->create();

    $response = $this->actingAs($this->user)->get(route('users.index'));

    $response->assertOk();
    $response->assertSee($this->user->name);
    foreach ($users as $user) {
        $response->assertSee($user->name);
        $response->assertSee($user->email);
    }
});

test('user list page is paginated', function () {
    User::factory()->count(15)->create();

    $response = $this->actingAs($this->user)->get(route('users.index'));

    $response->assertOk();
    $users = $response->viewData('users');
    expect($users)->toHaveCount(10);
});
