@extends('layouts.app')

@section('content')
<div class="flex items-center justify-center min-h-[60vh]">
    <div class="card bg-base-100 shadow-xl w-full max-w-md">
        <div class="card-body">
            <h2 class="card-title text-2xl font-bold justify-center mb-4">Two-Factor Authentication</h2>

            <div x-data="{ recovery: false }">
                <div x-show="!recovery">
                    <p class="text-sm text-base-content/70 mb-4 text-center">
                        Please enter the authentication code from your authenticator app.
                    </p>

                    <form method="POST" action="{{ route('two-factor.login') }}">
                        @csrf

                        <div class="form-control w-full mb-4">
                            <label class="label" for="code">
                                <span class="label-text">Authentication Code</span>
                            </label>
                            <input
                                type="text"
                                id="code"
                                name="code"
                                inputmode="numeric"
                                class="input input-bordered w-full @error('code') input-error @enderror"
                                autofocus
                                autocomplete="one-time-code"
                            >
                            @error('code')
                                <label class="label">
                                    <span class="label-text-alt text-error">{{ $message }}</span>
                                </label>
                            @enderror
                        </div>

                        <div class="form-control mt-6">
                            <button type="submit" class="btn btn-primary w-full">Verify</button>
                        </div>
                    </form>
                </div>

                <div x-show="recovery" x-cloak>
                    <p class="text-sm text-base-content/70 mb-4 text-center">
                        Please enter one of your emergency recovery codes.
                    </p>

                    <form method="POST" action="{{ route('two-factor.login') }}">
                        @csrf

                        <div class="form-control w-full mb-4">
                            <label class="label" for="recovery_code">
                                <span class="label-text">Recovery Code</span>
                            </label>
                            <input
                                type="text"
                                id="recovery_code"
                                name="recovery_code"
                                class="input input-bordered w-full @error('recovery_code') input-error @enderror"
                                autocomplete="one-time-code"
                            >
                            @error('recovery_code')
                                <label class="label">
                                    <span class="label-text-alt text-error">{{ $message }}</span>
                                </label>
                            @enderror
                        </div>

                        <div class="form-control mt-6">
                            <button type="submit" class="btn btn-primary w-full">Verify</button>
                        </div>
                    </form>
                </div>

                <div class="text-center mt-4">
                    <button
                        type="button"
                        class="link link-hover text-sm"
                        x-show="!recovery"
                        x-on:click="recovery = true"
                    >
                        Use a recovery code
                    </button>
                    <button
                        type="button"
                        class="link link-hover text-sm"
                        x-show="recovery"
                        x-cloak
                        x-on:click="recovery = false"
                    >
                        Use an authentication code
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
