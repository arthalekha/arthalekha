<?php

namespace App\Http\Requests;

use App\Enums\Frequency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreRecurringTransferRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'creditor_id' => ['nullable', 'exists:accounts,id,user_id,'.Auth::id(), 'required_with:debtor_id', 'different:debtor_id'],
            'debtor_id' => ['nullable', 'exists:accounts,id,user_id,'.Auth::id(), 'required_with:creditor_id', 'different:creditor_id'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999999'],
            'next_transaction_at' => ['required', 'date'],
            'frequency' => ['required', Rule::enum(Frequency::class)],
            'remaining_recurrences' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'creditor_id.exists' => 'The selected destination account is invalid or does not belong to you.',
            'debtor_id.exists' => 'The selected source account is invalid or does not belong to you.',
            'creditor_id.different' => 'The destination account must be different from the source account.',
            'debtor_id.different' => 'The source account must be different from the destination account.',
            'creditor_id.required_with' => 'The destination account is required when source account is provided.',
            'debtor_id.required_with' => 'The source account is required when destination account is provided.',
            'amount.min' => 'The amount must be greater than zero.',
            'remaining_recurrences.min' => 'The remaining recurrences must be at least 1.',
        ];
    }
}
