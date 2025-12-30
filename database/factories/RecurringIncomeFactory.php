<?php

namespace Database\Factories;

use App\Enums\Frequency;
use App\Models\Account;
use App\Models\Person;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RecurringIncome>
 */
class RecurringIncomeFactory extends Factory
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
            'person_id' => fake()->optional()->randomElement([Person::factory(), null]),
            'account_id' => Account::factory(),
            'description' => fake()->sentence(),
            'amount' => fake()->randomFloat(2, 100, 50000),
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

    public function forAccount(Account $account): static
    {
        return $this->state(fn (array $attributes) => [
            'account_id' => $account->id,
        ]);
    }

    public function fromPerson(Person $person): static
    {
        return $this->state(fn (array $attributes) => [
            'person_id' => $person->id,
        ]);
    }

    public function unlimited(): static
    {
        return $this->state(fn (array $attributes) => [
            'remaining_recurrences' => null,
        ]);
    }
}
