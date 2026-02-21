<x-layouts.app>
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
            <h2 class="card-title text-2xl font-bold mb-4">Edit Recurring Transfer</h2>

            <form action="{{ route('recurring-transfers.update', $recurringTransfer) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="form-control mb-4">
                    <label class="label" for="description">
                        <span class="label-text">Description <span class="text-error">*</span></span>
                    </label>
                    <input type="text" name="description" id="description"
                           value="{{ old('description', $recurringTransfer->description) }}"
                           class="input input-bordered @error('description') input-error @enderror"
                           placeholder="e.g., Monthly Savings, Investment Transfer" required>
                    @error('description')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div class="form-control">
                        <label class="label" for="amount">
                            <span class="label-text">Amount <span class="text-error">*</span></span>
                        </label>
                        <input type="number" name="amount" id="amount"
                               value="{{ old('amount', $recurringTransfer->amount) }}"
                               class="input input-bordered @error('amount') input-error @enderror"
                               step="0.01" min="0.01" placeholder="0.00" required>
                        @error('amount')
                            <label class="label">
                                <span class="label-text-alt text-error">{{ $message }}</span>
                            </label>
                        @enderror
                    </div>

                    <div class="form-control">
                        <label class="label" for="next_transaction_at">
                            <span class="label-text">Next Transaction Date <span class="text-error">*</span></span>
                        </label>
                        <input type="datetime-local" name="next_transaction_at" id="next_transaction_at"
                               value="{{ old('next_transaction_at', $recurringTransfer->next_transaction_at->format('Y-m-d\TH:i')) }}"
                               class="input input-bordered @error('next_transaction_at') input-error @enderror" required>
                        @error('next_transaction_at')
                            <label class="label">
                                <span class="label-text-alt text-error">{{ $message }}</span>
                            </label>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div class="form-control">
                        <label class="label" for="frequency">
                            <span class="label-text">Frequency <span class="text-error">*</span></span>
                        </label>
                        <select name="frequency" id="frequency"
                                class="select select-bordered @error('frequency') select-error @enderror" required>
                            <option value="">Select frequency</option>
                            @foreach ($frequencies as $frequency)
                                <option value="{{ $frequency->value }}"
                                    {{ old('frequency', $recurringTransfer->frequency->value) == $frequency->value ? 'selected' : '' }}>
                                    {{ ucfirst($frequency->value) }}
                                </option>
                            @endforeach
                        </select>
                        @error('frequency')
                            <label class="label">
                                <span class="label-text-alt text-error">{{ $message }}</span>
                            </label>
                        @enderror
                    </div>

                    <div class="form-control">
                        <label class="label" for="remaining_recurrences">
                            <span class="label-text">Remaining Recurrences (Optional)</span>
                        </label>
                        <input type="number" name="remaining_recurrences" id="remaining_recurrences"
                               value="{{ old('remaining_recurrences', $recurringTransfer->remaining_recurrences) }}"
                               class="input input-bordered @error('remaining_recurrences') input-error @enderror"
                               min="1" placeholder="Leave empty for unlimited">
                        @error('remaining_recurrences')
                            <label class="label">
                                <span class="label-text-alt text-error">{{ $message }}</span>
                            </label>
                        @enderror
                    </div>
                </div>

                <div class="form-control mb-4">
                    <label class="label" for="debtor_id">
                        <span class="label-text">From Account (Source, Optional)</span>
                    </label>
                    <select name="debtor_id" id="debtor_id"
                            class="select select-bordered @error('debtor_id') select-error @enderror">
                        <option value="" {{ old('debtor_id', $recurringTransfer->debtor_id) === null ? 'selected' : '' }}>No account (skip transaction)</option>
                        @foreach ($accounts as $account)
                            <option value="{{ $account->id }}"
                                {{ old('debtor_id', $recurringTransfer->debtor_id) == $account->id ? 'selected' : '' }}>
                                {{ $account->label }}
                            </option>
                        @endforeach
                    </select>
                    @error('debtor_id')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                <div class="form-control mb-4">
                    <label class="label" for="creditor_id">
                        <span class="label-text">To Account (Destination, Optional)</span>
                    </label>
                    <select name="creditor_id" id="creditor_id"
                            class="select select-bordered @error('creditor_id') select-error @enderror">
                        <option value="" {{ old('creditor_id', $recurringTransfer->creditor_id) === null ? 'selected' : '' }}>No account (skip transaction)</option>
                        @foreach ($accounts as $account)
                            <option value="{{ $account->id }}"
                                {{ old('creditor_id', $recurringTransfer->creditor_id) == $account->id ? 'selected' : '' }}>
                                {{ $account->label }}
                            </option>
                        @endforeach
                    </select>
                    @error('creditor_id')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                <x-tag-selector :tags="$tags" :selected="$recurringTransfer->tags->pluck('id')->toArray()" />

                <div class="flex justify-end gap-2 mt-6">
                    <a href="{{ route('recurring-transfers.index') }}" class="btn btn-ghost">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Recurring Transfer</button>
                </div>
            </form>
        </div>
    </div>
</div>
</x-layouts.app>
