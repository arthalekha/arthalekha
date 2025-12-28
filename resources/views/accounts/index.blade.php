@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Accounts</h1>
        <a href="{{ route('accounts.create') }}" class="btn btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Add Account
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            @if ($accounts->isEmpty())
                <div class="text-center py-8">
                    <p class="text-base-content/70">No accounts found.</p>
                    <a href="{{ route('accounts.create') }}" class="btn btn-primary btn-sm mt-4">Create your first account</a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Identifier</th>
                                <th class="text-right">Current Balance</th>
                                <th>Initial Date</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($accounts as $account)
                                <tr>
                                    <td class="font-medium">{{ $account->name }}</td>
                                    <td>
                                        <span class="badge badge-ghost">{{ ucfirst(str_replace('_', ' ', $account->account_type->value)) }}</span>
                                    </td>
                                    <td class="text-sm text-base-content/70">
                                        {{ $account->identifier ?? '-' }}
                                    </td>
                                    <td class="text-right font-mono {{ $account->current_balance >= 0 ? 'text-success' : 'text-error' }}">
                                        {{ number_format($account->current_balance, 2) }}
                                    </td>
                                    <td class="text-sm text-base-content/70">
                                        {{ $account->initial_date?->format('M d, Y') ?? '-' }}
                                    </td>
                                    <td>
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('accounts.show', $account) }}" class="btn btn-ghost btn-sm">
                                                View
                                            </a>
                                            <a href="{{ route('accounts.edit', $account) }}" class="btn btn-ghost btn-sm">
                                                Edit
                                            </a>
                                            <form action="{{ route('accounts.destroy', $account) }}" method="POST" class="inline"
                                                  onsubmit="return confirm('Are you sure you want to delete this account?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-ghost btn-sm text-error">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $accounts->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

