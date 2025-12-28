@extends('layouts.app')

@section('content')
<div class="flex items-center justify-center min-h-[60vh]">
    <div class="card bg-base-100 shadow-xl w-full max-w-md">
        <div class="card-body">
            <h2 class="card-title text-2xl font-bold justify-center mb-4">Confirm Password</h2>

            <p class="text-sm text-base-content/70 mb-4 text-center">
                This is a secure area. Please confirm your password before continuing.
            </p>

            <form method="POST" action="{{ route('password.confirm') }}">
                @csrf

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
                        autofocus
                        autocomplete="current-password"
                    >
                    @error('password')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                <div class="form-control mt-6">
                    <button type="submit" class="btn btn-primary w-full">Confirm</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
