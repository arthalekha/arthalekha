<?php

namespace App\Http\Requests;

use App\Enums\Frequency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreRecurringExpenseRequest extends FormRequest
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
            'account_id' => ['nullable', 'exists:accounts,id,user_id,'.Auth::id()],
            'person_id' => ['nullable', 'exists:people,id'],
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
            'account_id.exists' => 'The selected account is invalid or does not belong to you.',
            'amount.min' => 'The amount must be greater than zero.',
            'remaining_recurrences.min' => 'The remaining recurrences must be at least 1.',
        ];
    }
}
