@extends('layouts.app')

@section('content')
<div class="flex flex-col items-center justify-center min-h-[60vh]">
    <div class="card bg-base-100 shadow-xl w-full max-w-2xl">
        <div class="card-body">
            <h2 class="card-title text-2xl font-bold">Welcome, {{ Auth::user()->name }}!</h2>
            <p class="text-base-content/70">You are logged in.</p>

            <div class="divider"></div>

            <div class="stats shadow">
                <div class="stat">
                    <div class="stat-title">Email</div>
                    <div class="stat-value text-lg">{{ Auth::user()->email }}</div>
                </div>
                <div class="stat">
                    <div class="stat-title">Member Since</div>
                    <div class="stat-value text-lg">{{ Auth::user()->created_at->format('M d, Y') }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
