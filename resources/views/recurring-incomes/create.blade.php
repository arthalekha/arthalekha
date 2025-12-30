@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('recurring-incomes.index') }}" class="btn btn-ghost btn-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Back to Recurring Incomes
        </a>
    </div>

    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <h2 class="card-title text-2xl font-bold mb-4">Create Recurring Income</h2>

            <form action="{{ route('recurring-incomes.store') }}" method="POST">
                @csrf

                <div class="form-control mb-4">
                    <label class="label" for="description">
                        <span class="label-text">Description <span class="text-error">*</span></span>
                    </label>
                    <input type="text" name="description" id="description" value="{{ old('description') }}"
                           class="input input-bordered @error('description') input-error @enderror"
                           placeholder="e.g., Monthly Salary, Rental Income" required>
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
                        <input type="number" name="amount" id="amount" value="{{ old('amount') }}"
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
                               value="{{ old('next_transaction_at', now()->format('Y-m-d\TH:i')) }}"
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
                                <option value="{{ $frequency->value }}" {{ old('frequency') == $frequency->value ? 'selected' : '' }}>
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
                               value="{{ old('remaining_recurrences') }}"
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
                    <label class="label" for="account_id">
                        <span class="label-text">Account <span class="text-error">*</span></span>
                    </label>
                    <select name="account_id" id="account_id"
                            class="select select-bordered @error('account_id') select-error @enderror" required>
                        <option value="">Select account</option>
                        @foreach ($accounts as $account)
                            <option value="{{ $account->id }}" {{ old('account_id') == $account->id ? 'selected' : '' }}>
                                {{ $account->name }} ({{ ucfirst(str_replace('_', ' ', $account->account_type->value)) }})
                            </option>
                        @endforeach
                    </select>
                    @error('account_id')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                <div class="form-control mb-4">
                    <label class="label" for="person_id">
                        <span class="label-text">Received From (Optional)</span>
                    </label>
                    <select name="person_id" id="person_id"
                            class="select select-bordered @error('person_id') select-error @enderror">
                        <option value="">Select person</option>
                        @foreach ($people as $person)
                            <option value="{{ $person->id }}" {{ old('person_id') == $person->id ? 'selected' : '' }}>
                                {{ $person->name }}{{ $person->nick_name ? ' ('.$person->nick_name.')' : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('person_id')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                <x-tag-selector :tags="$tags" />

                <div class="flex justify-end gap-2 mt-6">
                    <a href="{{ route('recurring-incomes.index') }}" class="btn btn-ghost">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create Recurring Income</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
