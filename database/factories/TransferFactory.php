<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transfer>
 */
class TransferFactory extends Factory
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
            'transacted_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'amount' => fake()->randomFloat(2, 100, 10000),
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
}
