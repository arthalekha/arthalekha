<x-layouts.app>
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
            <h2 class="card-title text-2xl font-bold mb-4">Record Transfer</h2>

            <form action="{{ route('transfers.store') }}" method="POST">
                @csrf

                <div class="form-control mb-4">
                    <label class="label" for="description">
                        <span class="label-text">Description <span class="text-error">*</span></span>
                    </label>
                    <input type="text" name="description" id="description" value="{{ old('description', request('description')) }}"
                           class="input input-bordered @error('description') input-error @enderror"
                           placeholder="e.g., Transfer to savings" required>
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
                        <input type="number" name="amount" id="amount" value="{{ old('amount', request('amount')) }}"
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
                    <label class="label" for="debtor_id">
                        <span class="label-text">From Account (Source) <span class="text-error">*</span></span>
                    </label>
                    <x-searchable-select
                        name="debtor_id"
                        :options="$accounts->map(fn($account) => ['value' => $account->id, 'label' => $account->label])->toArray()"
                        :selected="old('debtor_id', request('debtor_id'))"
                        placeholder="Select source account"
                        :required="true"
                        :has-error="$errors->has('debtor_id')"
                    />
                    @error('debtor_id')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                <div class="flex justify-center my-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-base-content/50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                    </svg>
                </div>

                <div class="form-control mb-4">
                    <label class="label" for="creditor_id">
                        <span class="label-text">To Account (Destination) <span class="text-error">*</span></span>
                    </label>
                    <x-searchable-select
                        name="creditor_id"
                        :options="$accounts->map(fn($account) => ['value' => $account->id, 'label' => $account->label])->toArray()"
                        :selected="old('creditor_id', request('creditor_id'))"
                        placeholder="Select destination account"
                        :required="true"
                        :has-error="$errors->has('creditor_id')"
                    />
                    @error('creditor_id')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                <x-tag-selector :tags="$tags" :selected="array_map('intval', request('tags', []))" />

                <div class="flex justify-end gap-2 mt-6">
                    <a href="{{ route('transfers.index') }}" class="btn btn-ghost">Cancel</a>
                    <button type="submit" class="btn btn-primary">Record Transfer</button>
                </div>
            </form>
        </div>
    </div>
</div>
</x-layouts.app>
