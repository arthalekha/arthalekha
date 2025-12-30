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
            <h2 class="card-title text-2xl font-bold mb-4">Edit Transfer</h2>

            <form action="{{ route('transfers.update', $transfer) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="form-control mb-4">
                    <label class="label" for="description">
                        <span class="label-text">Description <span class="text-error">*</span></span>
                    </label>
                    <input type="text" name="description" id="description" value="{{ old('description', $transfer->description) }}"
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
                        <input type="number" name="amount" id="amount" value="{{ old('amount', $transfer->amount) }}"
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
                               value="{{ old('transacted_at', $transfer->transacted_at->format('Y-m-d\TH:i')) }}"
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
                    <select name="debtor_id" id="debtor_id"
                            class="select select-bordered @error('debtor_id') select-error @enderror" required>
                        <option value="">Select source account</option>
                        @foreach ($accounts as $account)
                            <option value="{{ $account->id }}" {{ old('debtor_id', $transfer->debtor_id) == $account->id ? 'selected' : '' }}>
                                {{ $account->name }} ({{ ucfirst(str_replace('_', ' ', $account->account_type->value)) }})
                            </option>
                        @endforeach
                    </select>
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
                    <select name="creditor_id" id="creditor_id"
                            class="select select-bordered @error('creditor_id') select-error @enderror" required>
                        <option value="">Select destination account</option>
                        @foreach ($accounts as $account)
                            <option value="{{ $account->id }}" {{ old('creditor_id', $transfer->creditor_id) == $account->id ? 'selected' : '' }}>
                                {{ $account->name }} ({{ ucfirst(str_replace('_', ' ', $account->account_type->value)) }})
                            </option>
                        @endforeach
                    </select>
                    @error('creditor_id')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                @if($tags->count() > 0)
                <div class="form-control mb-4">
                    <label class="label">
                        <span class="label-text">Tags (Optional)</span>
                    </label>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($tags as $tag)
                            <label class="cursor-pointer flex items-center gap-2 px-3 py-1 rounded-full border border-base-300 hover:bg-base-200 transition-colors has-[:checked]:bg-primary/10 has-[:checked]:border-primary">
                                <input type="checkbox" name="tags[]" value="{{ $tag->id }}"
                                       class="checkbox checkbox-sm checkbox-primary"
                                       {{ in_array($tag->id, old('tags', $transfer->tags->pluck('id')->toArray())) ? 'checked' : '' }}>
                                <span class="text-sm" style="color: {{ $tag->color }}">{{ $tag->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('tags')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>
                @endif

                <div class="flex justify-end gap-2 mt-6">
                    <a href="{{ route('transfers.index') }}" class="btn btn-ghost">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Transfer</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

