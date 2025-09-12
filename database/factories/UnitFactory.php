<?php

namespace Database\Factories;

use App\Models\Subject;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Unit>
 */
class UnitFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Unit::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subject_id' => Subject::factory(),
            'name' => fake()->words(2, true).' Unit',
            'description' => fake()->optional()->sentence(10),
            'target_completion_date' => fake()->optional(0.7)->dateTimeBetween('now', '+6 months'),
        ];
    }

    /**
     * Create a unit with a specific completion date.
     */
    public function withCompletionDate(\DateTimeInterface $date): static
    {
        return $this->state(fn (array $attributes) => [
            'target_completion_date' => $date,
        ]);
    }

    /**
     * Create a unit without a completion date.
     */
    public function withoutCompletionDate(): static
    {
        return $this->state(fn (array $attributes) => [
            'target_completion_date' => null,
        ]);
    }
}
