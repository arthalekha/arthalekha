<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use SourcedOpen\Tags\Models\Tag;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\SourcedOpen\Tags\Models\Tag>
 */
class TagFactory extends Factory
{
    protected $model = Tag::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'color' => fake()->hexColor(),
        ];
    }
}
