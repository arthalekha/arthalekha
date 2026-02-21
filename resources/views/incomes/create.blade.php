<x-layouts.app>
<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('incomes.index') }}" class="btn btn-ghost btn-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Back to Incomes
        </a>
    </div>

    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <h2 class="card-title text-2xl font-bold mb-4">Record Income</h2>

            <form action="{{ route('incomes.store') }}" method="POST">
                @csrf

                <div class="form-control mb-4">
                    <label class="label" for="description">
                        <span class="label-text">Description <span class="text-error">*</span></span>
                    </label>
                    <input type="text" name="description" id="description" value="{{ old('description') }}"
                           class="input input-bordered @error('description') input-error @enderror"
                           placeholder="e.g., Salary, Freelance payment" required>
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
                        <label class="label" for="transacted_at">
                            <span class="label-text">Date <span class="text-error">*</span></span>
                        </label>
                        <input type="datetime-local" name="transacted_at" id="transacted_at"
                               value="{{ old('transacted_at', now()->format('Y-m-d\TH:i')) }}"
                               class="input input-bordered @error('transacted_at') input-error @enderror" required>
                        @error('transacted_at')
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
                                {{ $account->label }}
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
                    <a href="{{ route('incomes.index') }}" class="btn btn-ghost">Cancel</a>
                    <button type="submit" class="btn btn-primary">Record Income</button>
                </div>
            </form>
        </div>
    </div>
</div>
</x-layouts.app>
