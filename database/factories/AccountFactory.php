<?php

namespace Database\Factories;

use App\Enums\AccountType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Account>
 */
class AccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $initialBalance = fake()->randomFloat(2, 0, 100000);

        return [
            'user_id' => User::factory(),
            'name' => fake()->randomElement(['ICICI', 'AXIS', 'HDFC', 'CASH']),
            'identifier' => fake()->optional()->bankAccountNumber(),
            'account_type' => fake()->randomElement(AccountType::cases()),
            'current_balance' => $initialBalance,
            'initial_date' => fake()->date(),
            'initial_balance' => $initialBalance,
            'data' => null,
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    public function ofType(AccountType $type): static
    {
        return $this->state(fn (array $attributes) => [
            'account_type' => $type,
        ]);
    }
}
