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
            <h2 class="card-title text-2xl font-bold mb-4">Create Account</h2>

            <form action="{{ route('accounts.store') }}" method="POST">
                @csrf

                <div class="form-control mb-4">
                    <label class="label" for="name">
                        <span class="label-text">Account Name <span class="text-error">*</span></span>
                    </label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}"
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
                            <option value="{{ $type->value }}" {{ old('account_type') === $type->value ? 'selected' : '' }}>
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
                    <input type="text" name="identifier" id="identifier" value="{{ old('identifier') }}"
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
                        <input type="number" name="initial_balance" id="initial_balance" value="{{ old('initial_balance', '0.00') }}"
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
                        <input type="date" name="initial_date" id="initial_date" value="{{ old('initial_date') }}"
                               class="input input-bordered @error('initial_date') input-error @enderror">
                        @error('initial_date')
                            <label class="label">
                                <span class="label-text-alt text-error">{{ $message }}</span>
                            </label>
                        @enderror
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-6">
                    <a href="{{ route('accounts.index') }}" class="btn btn-ghost">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create Account</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

