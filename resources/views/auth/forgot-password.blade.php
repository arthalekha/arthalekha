@extends('layouts.app')

@section('content')
<div class="flex items-center justify-center min-h-[60vh]">
    <div class="card bg-base-100 shadow-xl w-full max-w-md">
        <div class="card-body">
            <h2 class="card-title text-2xl font-bold justify-center mb-4">Forgot Password</h2>

            <p class="text-sm text-base-content/70 mb-4 text-center">
                Enter your email address and we'll send you a link to reset your password.
            </p>

            @if (session('status'))
                <div class="alert alert-success mb-4">
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}">
                @csrf

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
                        autofocus
                        autocomplete="username"
                    >
                    @error('email')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                <div class="form-control mt-6">
                    <button type="submit" class="btn btn-primary w-full">Send Reset Link</button>
                </div>

                <div class="text-center mt-4">
                    <a href="{{ route('login') }}" class="link link-hover text-sm">
                        Back to Login
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
