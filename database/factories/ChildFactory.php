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
            'age' => $this->faker->numberBetween(3, 18),
            'independence_level' => $this->faker->numberBetween(1, 4),
        ];
    }

    /**
     * Create a preschool child (ages 3-5)
     */
    public function preschool(): static
    {
        return $this->state(fn (array $attributes) => [
            'age' => $this->faker->numberBetween(3, 5),
            'independence_level' => 1, // Typically guided
        ]);
    }

    /**
     * Create an elementary school child (ages 6-12)
     */
    public function elementary(): static
    {
        return $this->state(fn (array $attributes) => [
            'age' => $this->faker->numberBetween(6, 12),
            'independence_level' => $this->faker->numberBetween(1, 3),
        ]);
    }

    /**
     * Create a high school child (ages 13-18)
     */
    public function highSchool(): static
    {
        return $this->state(fn (array $attributes) => [
            'age' => $this->faker->numberBetween(13, 18),
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
