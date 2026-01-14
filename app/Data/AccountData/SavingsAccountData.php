<?php

namespace App\Data\AccountData;

readonly class SavingsAccountData implements AccountData
{
    public function __construct(
        public ?float $rateOfInterest = null,
        public ?string $interestFrequency = null,
        public ?string $averageBalanceFrequency = null,
        public ?float $averageBalanceAmount = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'rate_of_interest' => $this->rateOfInterest,
            'interest_frequency' => $this->interestFrequency,
            'average_balance_frequency' => $this->averageBalanceFrequency,
            'average_balance_amount' => $this->averageBalanceAmount,
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
            averageBalanceFrequency: $data['average_balance_frequency'] ?? null,
            averageBalanceAmount: $data['average_balance_amount'] ?? null,
        );
    }
}
