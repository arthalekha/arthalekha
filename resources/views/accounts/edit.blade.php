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
            <h2 class="card-title text-2xl font-bold mb-4">Edit Account</h2>

            <form action="{{ route('accounts.update', $account) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="form-control mb-4">
                    <label class="label" for="name">
                        <span class="label-text">Account Name <span class="text-error">*</span></span>
                    </label>
                    <input type="text" name="name" id="name" value="{{ old('name', $account->name) }}"
                           class="input input-bordered @error('name') input-error @enderror"
                           placeholder="e.g., Main Savings Account" required>
                    @error('name')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                <div class="form-control mb-4">
                    <label class="label" for="account_type">
                        <span class="label-text">Account Type <span class="text-error">*</span></span>
                    </label>
                    <select name="account_type" id="account_type"
                            class="select select-bordered @error('account_type') select-error @enderror" required>
                        <option value="">Select account type</option>
                        @foreach ($accountTypes as $type)
                            <option value="{{ $type->value }}" {{ old('account_type', $account->account_type->value) === $type->value ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_', ' ', $type->value)) }}
                            </option>
                        @endforeach
                    </select>
                    @error('account_type')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                <div class="form-control mb-4">
                    <label class="label" for="identifier">
                        <span class="label-text">Identifier / Account Number</span>
                    </label>
                    <input type="text" name="identifier" id="identifier" value="{{ old('identifier', $account->identifier) }}"
                           class="input input-bordered @error('identifier') input-error @enderror"
                           placeholder="e.g., XXXX-XXXX-1234">
                    @error('identifier')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div class="form-control">
                        <label class="label" for="initial_balance">
                            <span class="label-text">Initial Balance <span class="text-error">*</span></span>
                        </label>
                        <input type="number" name="initial_balance" id="initial_balance" value="{{ old('initial_balance', $account->initial_balance) }}"
                               class="input input-bordered @error('initial_balance') input-error @enderror"
                               step="0.01" placeholder="0.00" required>
                        @error('initial_balance')
                            <label class="label">
                                <span class="label-text-alt text-error">{{ $message }}</span>
                            </label>
                        @enderror
                    </div>

                    <div class="form-control">
                        <label class="label" for="initial_date">
                            <span class="label-text">Initial Date</span>
                        </label>
                        <input type="date" name="initial_date" id="initial_date" value="{{ old('initial_date', $account->initial_date?->format('Y-m-d')) }}"
                               class="input input-bordered @error('initial_date') input-error @enderror">
                        @error('initial_date')
                            <label class="label">
                                <span class="label-text-alt text-error">{{ $message }}</span>
                            </label>
                        @enderror
                    </div>
                </div>

                {{-- Savings Account Fields --}}
                <div id="savings-fields" class="hidden">
                    <div class="divider">Savings Account Details</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div class="form-control">
                            <label class="label" for="data_rate_of_interest_savings">
                                <span class="label-text">Rate of Interest (%)</span>
                            </label>
                            <input type="number" name="data[rate_of_interest]" id="data_rate_of_interest_savings"
                                   value="{{ old('data.rate_of_interest', $account->data['rate_of_interest'] ?? '') }}"
                                   class="input input-bordered @error('data.rate_of_interest') input-error @enderror"
                                   step="0.01" min="0" max="100" placeholder="e.g., 4.5">
                            @error('data.rate_of_interest')
                                <label class="label">
                                    <span class="label-text-alt text-error">{{ $message }}</span>
                                </label>
                            @enderror
                        </div>

                        <div class="form-control">
                            <label class="label" for="data_interest_frequency_savings">
                                <span class="label-text">Interest Frequency</span>
                            </label>
                            <select name="data[interest_frequency]" id="data_interest_frequency_savings"
                                    class="select select-bordered @error('data.interest_frequency') select-error @enderror">
                                <option value="">Select frequency</option>
                                @foreach ($frequencies as $frequency)
                                    <option value="{{ $frequency->value }}" {{ old('data.interest_frequency', $account->data['interest_frequency'] ?? '') === $frequency->value ? 'selected' : '' }}>
                                        {{ ucfirst($frequency->value) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('data.interest_frequency')
                                <label class="label">
                                    <span class="label-text-alt text-error">{{ $message }}</span>
                                </label>
                            @enderror
                        </div>

                        <div class="form-control">
                            <label class="label" for="data_average_balance_frequency">
                                <span class="label-text">Average Balance Frequency</span>
                            </label>
                            <select name="data[average_balance_frequency]" id="data_average_balance_frequency"
                                    class="select select-bordered @error('data.average_balance_frequency') select-error @enderror">
                                <option value="">Select frequency</option>
                                @foreach ($frequencies as $frequency)
                                    <option value="{{ $frequency->value }}" {{ old('data.average_balance_frequency', $account->data['average_balance_frequency'] ?? '') === $frequency->value ? 'selected' : '' }}>
                                        {{ ucfirst($frequency->value) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('data.average_balance_frequency')
                                <label class="label">
                                    <span class="label-text-alt text-error">{{ $message }}</span>
                                </label>
                            @enderror
                        </div>

                        <div class="form-control">
                            <label class="label" for="data_average_balance_amount">
                                <span class="label-text">Average Balance Amount</span>
                            </label>
                            <input type="number" name="data[average_balance_amount]" id="data_average_balance_amount"
                                   value="{{ old('data.average_balance_amount', $account->data['average_balance_amount'] ?? '') }}"
                                   class="input input-bordered @error('data.average_balance_amount') input-error @enderror"
                                   step="0.01" min="0" placeholder="e.g., 10000.00">
                            @error('data.average_balance_amount')
                                <label class="label">
                                    <span class="label-text-alt text-error">{{ $message }}</span>
                                </label>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Credit Card Fields --}}
                <div id="credit-card-fields" class="hidden">
                    <div class="divider">Credit Card Details</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div class="form-control">
                            <label class="label" for="data_rate_of_interest_cc">
                                <span class="label-text">Rate of Interest (%)</span>
                            </label>
                            <input type="number" name="data[rate_of_interest]" id="data_rate_of_interest_cc"
                                   value="{{ old('data.rate_of_interest', $account->data['rate_of_interest'] ?? '') }}"
                                   class="input input-bordered @error('data.rate_of_interest') input-error @enderror"
                                   step="0.01" min="0" max="100" placeholder="e.g., 24.0">
                            @error('data.rate_of_interest')
                                <label class="label">
                                    <span class="label-text-alt text-error">{{ $message }}</span>
                                </label>
                            @enderror
                        </div>

                        <div class="form-control">
                            <label class="label" for="data_interest_frequency_cc">
                                <span class="label-text">Interest Frequency</span>
                            </label>
                            <select name="data[interest_frequency]" id="data_interest_frequency_cc"
                                    class="select select-bordered @error('data.interest_frequency') select-error @enderror">
                                <option value="">Select frequency</option>
                                @foreach ($frequencies as $frequency)
                                    <option value="{{ $frequency->value }}" {{ old('data.interest_frequency', $account->data['interest_frequency'] ?? '') === $frequency->value ? 'selected' : '' }}>
                                        {{ ucfirst($frequency->value) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('data.interest_frequency')
                                <label class="label">
                                    <span class="label-text-alt text-error">{{ $message }}</span>
                                </label>
                            @enderror
                        </div>

                        <div class="form-control">
                            <label class="label" for="data_bill_generated_on">
                                <span class="label-text">Bill Generated On (Day of Month)</span>
                            </label>
                            <input type="number" name="data[bill_generated_on]" id="data_bill_generated_on"
                                   value="{{ old('data.bill_generated_on', $account->data['bill_generated_on'] ?? '') }}"
                                   class="input input-bordered @error('data.bill_generated_on') input-error @enderror"
                                   min="1" max="31" placeholder="e.g., 15">
                            @error('data.bill_generated_on')
                                <label class="label">
                                    <span class="label-text-alt text-error">{{ $message }}</span>
                                </label>
                            @enderror
                        </div>

                        <div class="form-control">
                            <label class="label" for="data_repayment_of_bill_after_days">
                                <span class="label-text">Repayment Due After (Days)</span>
                            </label>
                            <input type="number" name="data[repayment_of_bill_after_days]" id="data_repayment_of_bill_after_days"
                                   value="{{ old('data.repayment_of_bill_after_days', $account->data['repayment_of_bill_after_days'] ?? '') }}"
                                   class="input input-bordered @error('data.repayment_of_bill_after_days') input-error @enderror"
                                   min="1" max="60" placeholder="e.g., 20">
                            @error('data.repayment_of_bill_after_days')
                                <label class="label">
                                    <span class="label-text-alt text-error">{{ $message }}</span>
                                </label>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="alert alert-info mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>Current Balance: <strong class="font-mono">{{ number_format($account->current_balance, 2) }}</strong></span>
                </div>

                <div class="flex justify-end gap-2 mt-6">
                    <a href="{{ route('accounts.index') }}" class="btn btn-ghost">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Account</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const accountTypeSelect = document.getElementById('account_type');
        const savingsFields = document.getElementById('savings-fields');
        const creditCardFields = document.getElementById('credit-card-fields');

        function toggleFields() {
            const selectedType = accountTypeSelect.value;

            savingsFields.classList.add('hidden');
            creditCardFields.classList.add('hidden');

            // Disable inputs in hidden sections to prevent submission
            savingsFields.querySelectorAll('input, select').forEach(el => el.disabled = true);
            creditCardFields.querySelectorAll('input, select').forEach(el => el.disabled = true);

            if (selectedType === 'savings') {
                savingsFields.classList.remove('hidden');
                savingsFields.querySelectorAll('input, select').forEach(el => el.disabled = false);
            } else if (selectedType === 'credit_card') {
                creditCardFields.classList.remove('hidden');
                creditCardFields.querySelectorAll('input, select').forEach(el => el.disabled = false);
            }
        }

        accountTypeSelect.addEventListener('change', toggleFields);
        toggleFields(); // Initialize on page load
    });
</script>
@endpush

