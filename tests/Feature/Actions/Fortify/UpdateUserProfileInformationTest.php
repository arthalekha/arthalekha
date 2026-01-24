<?php

use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'name' => 'Original Name',
        'email' => 'original@example.com',
    ]);
    $this->action = new UpdateUserProfileInformation;
});

it('updates name and email successfully', function () {
    $this->action->update($this->user, [
        'name' => 'New Name',
        'email' => 'new@example.com',
    ]);

    $this->user->refresh();

    expect($this->user->name)->toBe('New Name');
    expect($this->user->email)->toBe('new@example.com');
});

it('updates only name when email stays the same', function () {
    $this->action->update($this->user, [
        'name' => 'Updated Name',
        'email' => 'original@example.com',
    ]);

    $this->user->refresh();

    expect($this->user->name)->toBe('Updated Name');
    expect($this->user->email)->toBe('original@example.com');
});

it('fails when name is missing', function () {
    $this->action->update($this->user, [
        'email' => 'new@example.com',
    ]);
})->throws(ValidationException::class);

it('fails when name exceeds 255 characters', function () {
    $this->action->update($this->user, [
        'name' => str_repeat('a', 256),
        'email' => 'new@example.com',
    ]);
})->throws(ValidationException::class);

it('fails when email is missing', function () {
    $this->action->update($this->user, [
        'name' => 'New Name',
    ]);
})->throws(ValidationException::class);

it('fails when email is invalid', function () {
    $this->action->update($this->user, [
        'name' => 'New Name',
        'email' => 'not-an-email',
    ]);
})->throws(ValidationException::class);

it('fails when email exceeds 255 characters', function () {
    $this->action->update($this->user, [
        'name' => 'New Name',
        'email' => str_repeat('a', 250).'@example.com',
    ]);
})->throws(ValidationException::class);

it('fails when email is already taken by another user', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->action->update($this->user, [
        'name' => 'New Name',
        'email' => 'taken@example.com',
    ]);
})->throws(ValidationException::class);

it('allows user to keep their own email', function () {
    $this->action->update($this->user, [
        'name' => 'New Name',
        'email' => 'original@example.com',
    ]);

    $this->user->refresh();

    expect($this->user->email)->toBe('original@example.com');
});

it('validates errors are stored in updateProfileInformation error bag', function () {
    try {
        $this->action->update($this->user, [
            'name' => '',
            'email' => 'new@example.com',
        ]);
    } catch (ValidationException $e) {
        expect($e->errorBag)->toBe('updateProfileInformation');

        return;
    }

    $this->fail('ValidationException was not thrown');
});

it('resets email verification and sends notification when email changes for MustVerifyEmail user', function () {
    Notification::fake();

    $verifiableUser = new class extends User implements MustVerifyEmail
    {
        protected $table = 'users';
    };

    $verifiableUser->forceFill([
        'name' => 'Test User',
        'email' => 'verified@example.com',
        'email_verified_at' => now(),
        'password' => 'password',
    ])->save();

    $this->action->update($verifiableUser, [
        'name' => 'Updated Name',
        'email' => 'newemail@example.com',
    ]);

    $verifiableUser->refresh();

    expect($verifiableUser->name)->toBe('Updated Name');
    expect($verifiableUser->email)->toBe('newemail@example.com');
    expect($verifiableUser->email_verified_at)->toBeNull();

    Notification::assertSentTo($verifiableUser, Illuminate\Auth\Notifications\VerifyEmail::class);
});

it('does not reset email verification when email stays the same for MustVerifyEmail user', function () {
    Notification::fake();

    $verifiableUser = new class extends User implements MustVerifyEmail
    {
        protected $table = 'users';
    };

    $originalVerifiedAt = now()->subDay();

    $verifiableUser->forceFill([
        'name' => 'Test User',
        'email' => 'verified@example.com',
        'email_verified_at' => $originalVerifiedAt,
        'password' => 'password',
    ])->save();

    $this->action->update($verifiableUser, [
        'name' => 'Updated Name',
        'email' => 'verified@example.com',
    ]);

    $verifiableUser->refresh();

    expect($verifiableUser->name)->toBe('Updated Name');
    expect($verifiableUser->email_verified_at)->not->toBeNull();

    Notification::assertNothingSent();
});
