<x-layouts.app>
<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('recurring-expenses.index') }}" class="btn btn-ghost btn-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Back to Recurring Expenses
        </a>
    </div>

    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <div class="flex justify-between items-start">
                <div>
                    <h2 class="card-title text-2xl font-bold">{{ $recurringExpense->description }}</h2>
                    <p class="text-error font-mono text-xl mt-1">-{{ number_format($recurringExpense->amount, 2) }}</p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('recurring-expenses.edit', $recurringExpense) }}" class="btn btn-ghost btn-sm">Edit</a>
                    <form action="{{ route('recurring-expenses.destroy', $recurringExpense) }}" method="POST" class="inline"
                          onsubmit="return confirm('Are you sure you want to delete this recurring expense?');">
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
                    <p class="mt-1">{{ $recurringExpense->next_transaction_at->format('F d, Y \a\t h:i A') }}</p>
                </div>

                <div>
                    <label class="text-sm font-medium text-base-content/70">Frequency</label>
                    <div class="mt-1">
                        <span class="badge badge-outline">{{ ucfirst($recurringExpense->frequency->value) }}</span>
                    </div>
                </div>

                <div>
                    <label class="text-sm font-medium text-base-content/70">Remaining Recurrences</label>
                    <p class="mt-1">{{ $recurringExpense->remaining_recurrences ?? 'Unlimited' }}</p>
                </div>

                <div>
                    <label class="text-sm font-medium text-base-content/70">Account</label>
                    <div class="mt-1">
                        <span class="badge badge-ghost">{{ $recurringExpense->account->name }}</span>
                        <span class="text-sm text-base-content/70 ml-2">
                            ({{ ucfirst(str_replace('_', ' ', $recurringExpense->account->account_type->value)) }})
                        </span>
                    </div>
                </div>

                @if ($recurringExpense->person)
                    <div>
                        <label class="text-sm font-medium text-base-content/70">Paid To</label>
                        <p class="mt-1">
                            {{ $recurringExpense->person->name }}
                            @if ($recurringExpense->person->nick_name)
                                <span class="text-base-content/70">({{ $recurringExpense->person->nick_name }})</span>
                            @endif
                        </p>
                    </div>
                @endif

                <div>
                    <label class="text-sm font-medium text-base-content/70">Tags</label>
                    <div class="mt-1">
                        <x-tag-display :tags="$recurringExpense->tags" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</x-layouts.app>
