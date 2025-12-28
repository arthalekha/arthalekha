@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Transfers</h1>
        <a href="{{ route('transfers.create') }}" class="btn btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Add Transfer
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
            @if ($transfers->isEmpty())
                <div class="text-center py-8">
                    <p class="text-base-content/70">No transfers found.</p>
                    <a href="{{ route('transfers.create') }}" class="btn btn-primary btn-sm mt-4">Record your first transfer</a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th>From</th>
                                <th>To</th>
                                <th class="text-right">Amount</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($transfers as $transfer)
                                <tr>
                                    <td class="text-sm">
                                        {{ $transfer->transacted_at->format('M d, Y') }}
                                    </td>
                                    <td class="font-medium">{{ $transfer->description }}</td>
                                    <td>
                                        <span class="badge badge-error badge-outline">{{ $transfer->debtor->name }}</span>
                                    </td>
                                    <td>
                                        <span class="badge badge-success badge-outline">{{ $transfer->creditor->name }}</span>
                                    </td>
                                    <td class="text-right font-mono text-info">
                                        {{ number_format($transfer->amount, 2) }}
                                    </td>
                                    <td>
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('transfers.show', $transfer) }}" class="btn btn-ghost btn-sm">
                                                View
                                            </a>
                                            <a href="{{ route('transfers.edit', $transfer) }}" class="btn btn-ghost btn-sm">
                                                Edit
                                            </a>
                                            <form action="{{ route('transfers.destroy', $transfer) }}" method="POST" class="inline"
                                                  onsubmit="return confirm('Are you sure you want to delete this transfer?');">
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
                    {{ $transfers->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

