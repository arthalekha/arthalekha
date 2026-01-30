<?php

namespace Database\Factories;

use App\Enums\Frequency;
use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RecurringTransfer>
 */
class RecurringTransferFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'creditor_id' => Account::factory(),
            'debtor_id' => Account::factory(),
            'description' => fake()->sentence(),
            'amount' => fake()->randomFloat(2, 100, 10000),
            'next_transaction_at' => fake()->dateTimeBetween('now', '+1 year'),
            'frequency' => fake()->randomElement(Frequency::cases()),
            'remaining_recurrences' => fake()->optional()->numberBetween(1, 24),
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    public function fromAccount(Account $account): static
    {
        return $this->state(fn (array $attributes) => [
            'debtor_id' => $account->id,
        ]);
    }

    public function toAccount(Account $account): static
    {
        return $this->state(fn (array $attributes) => [
            'creditor_id' => $account->id,
        ]);
    }

    public function unlimited(): static
    {
        return $this->state(fn (array $attributes) => [
            'remaining_recurrences' => null,
        ]);
    }

    public function withoutAccounts(): static
    {
        return $this->state(fn (array $attributes) => [
            'creditor_id' => null,
            'debtor_id' => null,
        ]);
    }
}
