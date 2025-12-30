@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('recurring-transfers.index') }}" class="btn btn-ghost btn-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Back to Recurring Transfers
        </a>
    </div>

    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <div class="flex justify-between items-start">
                <div>
                    <h2 class="card-title text-2xl font-bold">{{ $recurringTransfer->description }}</h2>
                    <p class="text-info font-mono text-xl mt-1">{{ number_format($recurringTransfer->amount, 2) }}</p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('recurring-transfers.edit', $recurringTransfer) }}" class="btn btn-ghost btn-sm">Edit</a>
                    <form action="{{ route('recurring-transfers.destroy', $recurringTransfer) }}" method="POST" class="inline"
                          onsubmit="return confirm('Are you sure you want to delete this recurring transfer?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-ghost btn-sm text-error">Delete</button>
                    </form>
                </div>
            </div>

            <div class="divider"></div>

            <div class="space-y-4">
                <div>
                    <label class="text-sm font-medium text-base-content/70">Next Transaction Date</label>
                    <p class="mt-1">{{ $recurringTransfer->next_transaction_at->format('F d, Y \a\t h:i A') }}</p>
                </div>

                <div>
                    <label class="text-sm font-medium text-base-content/70">Frequency</label>
                    <div class="mt-1">
                        <span class="badge badge-outline">{{ ucfirst($recurringTransfer->frequency->value) }}</span>
                    </div>
                </div>

                <div>
                    <label class="text-sm font-medium text-base-content/70">Remaining Recurrences</label>
                    <p class="mt-1">{{ $recurringTransfer->remaining_recurrences ?? 'Unlimited' }}</p>
                </div>

                <div>
                    <label class="text-sm font-medium text-base-content/70">From Account (Source)</label>
                    <div class="mt-1">
                        <span class="badge badge-ghost">{{ $recurringTransfer->debtor->name }}</span>
                        <span class="text-sm text-base-content/70 ml-2">
                            ({{ ucfirst(str_replace('_', ' ', $recurringTransfer->debtor->account_type->value)) }})
                        </span>
                    </div>
                </div>

                <div>
                    <label class="text-sm font-medium text-base-content/70">To Account (Destination)</label>
                    <div class="mt-1">
                        <span class="badge badge-ghost">{{ $recurringTransfer->creditor->name }}</span>
                        <span class="text-sm text-base-content/70 ml-2">
                            ({{ ucfirst(str_replace('_', ' ', $recurringTransfer->creditor->account_type->value)) }})
                        </span>
                    </div>
                </div>

                <div>
                    <label class="text-sm font-medium text-base-content/70">Tags</label>
                    <div class="mt-1">
                        <x-tag-display :tags="$recurringTransfer->tags" />
                    </div>
                </div>

                <div>
                    <label class="text-sm font-medium text-base-content/70">Created</label>
                    <p class="mt-1">{{ $recurringTransfer->created_at->format('F d, Y \a\t h:i A') }}</p>
                </div>

                <div>
                    <label class="text-sm font-medium text-base-content/70">Last Updated</label>
                    <p class="mt-1">{{ $recurringTransfer->updated_at->format('F d, Y \a\t h:i A') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
