<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreTransferRequest extends FormRequest
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
            'creditor_id' => ['required', 'exists:accounts,id,user_id,'.Auth::id(), 'different:debtor_id'],
            'debtor_id' => ['required', 'exists:accounts,id,user_id,'.Auth::id(), 'different:creditor_id'],
            'description' => ['required', 'string', 'max:255'],
            'transacted_at' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999999'],
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
            'amount.min' => 'The amount must be greater than zero.',
        ];
    }
}
