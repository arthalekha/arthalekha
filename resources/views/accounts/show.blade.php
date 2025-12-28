@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('accounts.index') }}" class="btn btn-ghost btn-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Back to Accounts
        </a>
    </div>

    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <div class="flex justify-between items-start">
                <div>
                    <h2 class="card-title text-2xl font-bold">{{ $account->name }}</h2>
                    <span class="badge badge-ghost mt-1">{{ ucfirst(str_replace('_', ' ', $account->account_type->value)) }}</span>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('accounts.edit', $account) }}" class="btn btn-ghost btn-sm">Edit</a>
                    <form action="{{ route('accounts.destroy', $account) }}" method="POST" class="inline"
                          onsubmit="return confirm('Are you sure you want to delete this account?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-ghost btn-sm text-error">Delete</button>
                    </form>
                </div>
            </div>

            <div class="divider"></div>

            <div class="stats stats-vertical lg:stats-horizontal shadow w-full mb-6">
                <div class="stat">
                    <div class="stat-title">Current Balance</div>
                    <div class="stat-value text-lg {{ $account->current_balance >= 0 ? 'text-success' : 'text-error' }}">
                        {{ number_format($account->current_balance, 2) }}
                    </div>
                </div>
                <div class="stat">
                    <div class="stat-title">Initial Balance</div>
                    <div class="stat-value text-lg">{{ number_format($account->initial_balance, 2) }}</div>
                </div>
                <div class="stat">
                    <div class="stat-title">Difference</div>
                    @php $diff = $account->current_balance - $account->initial_balance; @endphp
                    <div class="stat-value text-lg {{ $diff >= 0 ? 'text-success' : 'text-error' }}">
                        {{ $diff >= 0 ? '+' : '' }}{{ number_format($diff, 2) }}
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                @if ($account->identifier)
                    <div>
                        <label class="text-sm font-medium text-base-content/70">Identifier / Account Number</label>
                        <p class="mt-1 font-mono">{{ $account->identifier }}</p>
                    </div>
                @endif

                @if ($account->initial_date)
                    <div>
                        <label class="text-sm font-medium text-base-content/70">Initial Date</label>
                        <p class="mt-1">{{ $account->initial_date->format('F d, Y') }}</p>
                    </div>
                @endif

                <div>
                    <label class="text-sm font-medium text-base-content/70">Created</label>
                    <p class="mt-1">{{ $account->created_at->format('F d, Y \a\t h:i A') }}</p>
                </div>

                <div>
                    <label class="text-sm font-medium text-base-content/70">Last Updated</label>
                    <p class="mt-1">{{ $account->updated_at->format('F d, Y \a\t h:i A') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

