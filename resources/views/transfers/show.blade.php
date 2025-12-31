@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('transfers.index') }}" class="btn btn-ghost btn-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Back to Transfers
        </a>
    </div>

    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <div class="flex justify-between items-start">
                <div>
                    <h2 class="card-title text-2xl font-bold">{{ $transfer->description }}</h2>
                    <p class="text-info font-mono text-xl mt-1">{{ number_format($transfer->amount, 2) }}</p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('transfers.edit', $transfer) }}" class="btn btn-ghost btn-sm">Edit</a>
                    <form action="{{ route('transfers.destroy', $transfer) }}" method="POST" class="inline"
                          onsubmit="return confirm('Are you sure you want to delete this transfer?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-ghost btn-sm text-error">Delete</button>
                    </form>
                </div>
            </div>

            <div class="divider"></div>

            <div class="space-y-4">
                <div>
                    <label class="text-sm font-medium text-base-content/70">Transaction Date</label>
                    <p class="mt-1">{{ $transfer->transacted_at->format('F d, Y \a\t h:i A') }}</p>
                </div>

                <div class="bg-base-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div class="text-center flex-1">
                            <label class="text-sm font-medium text-base-content/70">From Account</label>
                            <div class="mt-1">
                                <span class="badge badge-error badge-outline badge-lg">{{ $transfer->debtor->name }}</span>
                            </div>
                            <p class="text-xs text-base-content/50 mt-1">
                                {{ ucfirst(str_replace('_', ' ', $transfer->debtor->account_type->value)) }}
                            </p>
                        </div>
                        <div class="px-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-info" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                        </div>
                        <div class="text-center flex-1">
                            <label class="text-sm font-medium text-base-content/70">To Account</label>
                            <div class="mt-1">
                                <span class="badge badge-success badge-outline badge-lg">{{ $transfer->creditor->name }}</span>
                            </div>
                            <p class="text-xs text-base-content/50 mt-1">
                                {{ ucfirst(str_replace('_', ' ', $transfer->creditor->account_type->value)) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

