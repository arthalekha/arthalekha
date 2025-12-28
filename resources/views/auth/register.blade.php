@extends('layouts.app')

@section('content')
<div class="flex items-center justify-center min-h-[60vh]">
    <div class="card bg-base-100 shadow-xl w-full max-w-md">
        <div class="card-body">
            <h2 class="card-title text-2xl font-bold justify-center mb-4">Register</h2>

            <form method="POST" action="{{ route('register') }}">
                @csrf

                <div class="form-control w-full mb-4">
                    <label class="label" for="name">
                        <span class="label-text">Name</span>
                    </label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name') }}"
                        class="input input-bordered w-full @error('name') input-error @enderror"
                        required
                        autofocus
                        autocomplete="name"
                    >
                    @error('name')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                <div class="form-control w-full mb-4">
                    <label class="label" for="email">
                        <span class="label-text">Email</span>
                    </label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        class="input input-bordered w-full @error('email') input-error @enderror"
                        required
                        autocomplete="username"
                    >
                    @error('email')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                <div class="form-control w-full mb-4">
                    <label class="label" for="password">
                        <span class="label-text">Password</span>
                    </label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="input input-bordered w-full @error('password') input-error @enderror"
                        required
                        autocomplete="new-password"
                    >
                    @error('password')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                <div class="form-control w-full mb-4">
                    <label class="label" for="password_confirmation">
                        <span class="label-text">Confirm Password</span>
                    </label>
                    <input
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        class="input input-bordered w-full"
                        required
                        autocomplete="new-password"
                    >
                </div>

                <div class="form-control mt-6">
                    <button type="submit" class="btn btn-primary w-full">Register</button>
                </div>

                <div class="divider">OR</div>

                <div class="text-center">
                    <p class="text-sm">
                        Already have an account?
                        <a href="{{ route('login') }}" class="link link-primary">Login</a>
                    </p>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
