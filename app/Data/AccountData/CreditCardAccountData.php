<?php

namespace App\Data\AccountData;

readonly class CreditCardAccountData implements AccountData
{
    public function __construct(
        public ?float $rateOfInterest = null,
        public ?string $interestFrequency = null,
        public ?int $billGeneratedOn = null,
        public ?int $repaymentOfBillAfterDays = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'rate_of_interest' => $this->rateOfInterest,
            'interest_frequency' => $this->interestFrequency,
            'bill_generated_on' => $this->billGeneratedOn,
            'repayment_of_bill_after_days' => $this->repaymentOfBillAfterDays,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            rateOfInterest: $data['rate_of_interest'] ?? null,
            interestFrequency: $data['interest_frequency'] ?? null,
            billGeneratedOn: $data['bill_generated_on'] ?? null,
            repaymentOfBillAfterDays: $data['repayment_of_bill_after_days'] ?? null,
        );
    }
}
