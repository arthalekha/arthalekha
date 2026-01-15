<?php

namespace App\Http\Requests;

use App\Enums\AccountType;
use App\Enums\Frequency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccountRequest extends FormRequest
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
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'identifier' => ['nullable', 'string', 'max:255'],
            'account_type' => ['required', Rule::enum(AccountType::class)],
            'initial_date' => ['nullable', 'date'],
            'initial_balance' => ['required', 'numeric', 'min:-999999999999', 'max:999999999999'],
            'data' => ['nullable', 'array'],
        ];

        $dataRules = match ($this->enum('account_type', AccountType::class)) {
            AccountType::Savings => [
                'data.rate_of_interest' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'data.interest_frequency' => ['nullable', Rule::enum(Frequency::class)],
                'data.average_balance_frequency' => ['nullable', Rule::enum(Frequency::class)],
                'data.average_balance_amount' => ['nullable', 'numeric', 'min:0'],
            ],
            AccountType::CreditCard => [
                'data.rate_of_interest' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'data.interest_frequency' => ['nullable', Rule::enum(Frequency::class)],
                'data.bill_generated_on' => ['nullable', 'integer', 'min:1', 'max:31'],
                'data.repayment_of_bill_after_days' => ['nullable', 'integer', 'min:1', 'max:60'],
            ],
            default => [],
        };

        return [...$rules, ...$dataRules];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'account_type.enum' => 'The selected account type is invalid.',
            'data.rate_of_interest.max' => 'The rate of interest must not exceed 100%.',
            'data.bill_generated_on.max' => 'The bill generated day must be between 1 and 31.',
        ];
    }
}
