<?php

use App\Actions\Fortify\UpdateUserPassword;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'password' => Hash::make('current-password'),
    ]);
    $this->action = new UpdateUserPassword;
});

it('updates password successfully with valid input', function () {
    $this->actingAs($this->user);

    $this->action->update($this->user, [
        'current_password' => 'current-password',
        'password' => 'new-password123',
        'password_confirmation' => 'new-password123',
    ]);

    $this->user->refresh();

    expect(Hash::check('new-password123', $this->user->password))->toBeTrue();
});

it('fails when current password is incorrect', function () {
    $this->actingAs($this->user);

    $this->action->update($this->user, [
        'current_password' => 'wrong-password',
        'password' => 'new-password123',
        'password_confirmation' => 'new-password123',
    ]);
})->throws(ValidationException::class);

it('fails when current password is missing', function () {
    $this->actingAs($this->user);

    $this->action->update($this->user, [
        'password' => 'new-password123',
        'password_confirmation' => 'new-password123',
    ]);
})->throws(ValidationException::class);

it('fails when new password is missing', function () {
    $this->actingAs($this->user);

    $this->action->update($this->user, [
        'current_password' => 'current-password',
    ]);
})->throws(ValidationException::class);

it('fails when password confirmation does not match', function () {
    $this->actingAs($this->user);

    $this->action->update($this->user, [
        'current_password' => 'current-password',
        'password' => 'new-password123',
        'password_confirmation' => 'different-password',
    ]);
})->throws(ValidationException::class);

it('validates errors are stored in updatePassword error bag', function () {
    $this->actingAs($this->user);

    try {
        $this->action->update($this->user, [
            'current_password' => 'wrong-password',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);
    } catch (ValidationException $e) {
        expect($e->errorBag)->toBe('updatePassword');

        return;
    }

    $this->fail('ValidationException was not thrown');
});

it('provides custom error message for incorrect current password', function () {
    $this->actingAs($this->user);

    try {
        $this->action->update($this->user, [
            'current_password' => 'wrong-password',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('current_password');
        expect($e->errors()['current_password'][0])->toBe('The provided password does not match your current password.');

        return;
    }

    $this->fail('ValidationException was not thrown');
});
