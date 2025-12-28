<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Person;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Expense>
 */
class ExpenseFactory extends Factory
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
            'transacted_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'amount' => fake()->randomFloat(2, 50, 10000),
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

    public function toPerson(Person $person): static
    {
        return $this->state(fn (array $attributes) => [
            'person_id' => $person->id,
        ]);
    }
}
