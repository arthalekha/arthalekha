@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('expenses.index') }}" class="btn btn-ghost btn-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Back to Expenses
        </a>
    </div>

    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <div class="flex justify-between items-start">
                <div>
                    <h2 class="card-title text-2xl font-bold">{{ $expense->description }}</h2>
                    <p class="text-error font-mono text-xl mt-1">-{{ number_format($expense->amount, 2) }}</p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('expenses.edit', $expense) }}" class="btn btn-ghost btn-sm">Edit</a>
                    <form action="{{ route('expenses.destroy', $expense) }}" method="POST" class="inline"
                          onsubmit="return confirm('Are you sure you want to delete this expense?');">
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
                    <p class="mt-1">{{ $expense->transacted_at->format('F d, Y \a\t h:i A') }}</p>
                </div>

                <div>
                    <label class="text-sm font-medium text-base-content/70">Account</label>
                    <div class="mt-1">
                        <span class="badge badge-ghost">{{ $expense->account->name }}</span>
                        <span class="text-sm text-base-content/70 ml-2">
                            ({{ ucfirst(str_replace('_', ' ', $expense->account->account_type->value)) }})
                        </span>
                    </div>
                </div>

                @if ($expense->person)
                    <div>
                        <label class="text-sm font-medium text-base-content/70">Paid To</label>
                        <p class="mt-1">
                            {{ $expense->person->name }}
                            @if ($expense->person->nick_name)
                                <span class="text-base-content/70">({{ $expense->person->nick_name }})</span>
                            @endif
                        </p>
                    </div>
                @endif

                <div>
                    <label class="text-sm font-medium text-base-content/70">Created</label>
                    <p class="mt-1">{{ $expense->created_at->format('F d, Y \a\t h:i A') }}</p>
                </div>

                <div>
                    <label class="text-sm font-medium text-base-content/70">Last Updated</label>
                    <p class="mt-1">{{ $expense->updated_at->format('F d, Y \a\t h:i A') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

