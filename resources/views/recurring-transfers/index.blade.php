@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Recurring Transfers</h1>
        <a href="{{ route('recurring-transfers.create') }}" class="btn btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Add Recurring Transfer
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

    <div class="card bg-base-100 shadow-xl mb-6">
        <div class="card-body">
            <form method="GET" action="{{ route('recurring-transfers.index') }}">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Search</span>
                        </label>
                        <input
                            type="text"
                            name="filter[search]"
                            value="{{ $filters['search'] }}"
                            placeholder="Search description..."
                            class="input input-bordered input-sm"
                        >
                    </div>

                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">From Account</span>
                        </label>
                        <select name="filter[debtor_id]" class="select select-bordered select-sm">
                            <option value="">All Accounts</option>
                            @foreach ($accounts as $account)
                                <option value="{{ $account->id }}" {{ $filters['debtor_id'] == $account->id ? 'selected' : '' }}>
                                    {{ $account->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">To Account</span>
                        </label>
                        <select name="filter[creditor_id]" class="select select-bordered select-sm">
                            <option value="">All Accounts</option>
                            @foreach ($accounts as $account)
                                <option value="{{ $account->id }}" {{ $filters['creditor_id'] == $account->id ? 'selected' : '' }}>
                                    {{ $account->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Frequency</span>
                        </label>
                        <select name="filter[frequency]" class="select select-bordered select-sm">
                            <option value="">All Frequencies</option>
                            @foreach ($frequencies as $frequency)
                                <option value="{{ $frequency->value }}" {{ $filters['frequency'] == $frequency->value ? 'selected' : '' }}>
                                    {{ ucfirst($frequency->value) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        Filter
                    </button>
                    <a href="{{ route('recurring-transfers.index') }}" class="btn btn-ghost btn-sm">
                        Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            @if ($recurringTransfers->isEmpty())
                <div class="text-center py-8">
                    <p class="text-base-content/70">No recurring transfers found.</p>
                    <a href="{{ route('recurring-transfers.create') }}" class="btn btn-primary btn-sm mt-4">Create your first recurring transfer</a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Next Date</th>
                                <th>Description</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Frequency</th>
                                <th>Remaining</th>
                                <th class="text-right">Amount</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recurringTransfers as $recurringTransfer)
                                <tr>
                                    <td class="text-sm">
                                        {{ $recurringTransfer->next_transaction_at->format('M d, Y') }}
                                    </td>
                                    <td class="font-medium">{{ $recurringTransfer->description }}</td>
                                    <td>
                                        <span class="badge badge-ghost">{{ $recurringTransfer->debtor->name }}</span>
                                    </td>
                                    <td>
                                        <span class="badge badge-ghost">{{ $recurringTransfer->creditor->name }}</span>
                                    </td>
                                    <td>
                                        <span class="badge badge-outline">{{ ucfirst($recurringTransfer->frequency->value) }}</span>
                                    </td>
                                    <td class="text-sm">
                                        {{ $recurringTransfer->remaining_recurrences ?? 'Unlimited' }}
                                    </td>
                                    <td class="text-right font-mono text-info">
                                        {{ number_format($recurringTransfer->amount, 2) }}
                                    </td>
                                    <td>
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('recurring-transfers.show', $recurringTransfer) }}" class="btn btn-ghost btn-sm">
                                                View
                                            </a>
                                            <a href="{{ route('recurring-transfers.edit', $recurringTransfer) }}" class="btn btn-ghost btn-sm">
                                                Edit
                                            </a>
                                            <form action="{{ route('recurring-transfers.destroy', $recurringTransfer) }}" method="POST" class="inline"
                                                  onsubmit="return confirm('Are you sure you want to delete this recurring transfer?');">
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
                    {{ $recurringTransfers->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
