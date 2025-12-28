@extends('layouts.app')

@section('content')
<div class="flex items-center justify-center min-h-[60vh]">
    <div class="card bg-base-100 shadow-xl w-full max-w-md">
        <div class="card-body">
            <h2 class="card-title text-2xl font-bold justify-center mb-4">Login</h2>

            @if (session('status'))
                <div class="alert alert-success mb-4">
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
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
                        autocomplete="current-password"
                    >
                    @error('password')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                <div class="form-control mb-4">
                    <label class="label cursor-pointer justify-start gap-2">
                        <input type="checkbox" name="remember" class="checkbox checkbox-primary checkbox-sm">
                        <span class="label-text">Remember me</span>
                    </label>
                </div>

                <div class="form-control mt-6">
                    <button type="submit" class="btn btn-primary w-full">Login</button>
                </div>

                <div class="divider">OR</div>

                <div class="text-center space-y-2">
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="link link-hover text-sm">
                            Forgot your password?
                        </a>
                    @endif

                    @if (Route::has('register'))
                        <p class="text-sm">
                            Don't have an account?
                            <a href="{{ route('register') }}" class="link link-primary">Register</a>
                        </p>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
