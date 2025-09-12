<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Child>
 */
class ChildFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'name' => $this->faker->firstName(),
            'grade' => $this->faker->randomElement(['PreK', 'K', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th']),
            'independence_level' => $this->faker->numberBetween(1, 4),
        ];
    }

    /**
     * Create a preschool child (PreK-K)
     */
    public function preschool(): static
    {
        return $this->state(fn (array $attributes) => [
            'grade' => $this->faker->randomElement(['PreK', 'K']),
            'independence_level' => 1, // Typically guided
        ]);
    }

    /**
     * Create an elementary school child (1st-5th grade)
     */
    public function elementary(): static
    {
        return $this->state(fn (array $attributes) => [
            'grade' => $this->faker->randomElement(['1st', '2nd', '3rd', '4th', '5th']),
            'independence_level' => $this->faker->numberBetween(1, 3),
        ]);
    }

    /**
     * Create a high school child (6th-12th grade)
     */
    public function highSchool(): static
    {
        return $this->state(fn (array $attributes) => [
            'grade' => $this->faker->randomElement(['6th', '7th', '8th', '9th', '10th', '11th', '12th']),
            'independence_level' => $this->faker->numberBetween(2, 4),
        ]);
    }

    /**
     * Create a child with advanced independence level
     */
    public function advanced(): static
    {
        return $this->state(fn (array $attributes) => [
            'independence_level' => 4,
        ]);
    }
}
