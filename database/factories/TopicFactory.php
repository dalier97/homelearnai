<?php

namespace Database\Factories;

use App\Models\Topic;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Topic>
 */
class TopicFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Topic::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'unit_id' => Unit::factory(),
            'title' => fake()->sentence(3),
            'estimated_minutes' => fake()->numberBetween(15, 120),
            'prerequisites' => [],
            'required' => fake()->boolean(80), // 80% chance of being required
        ];
    }

    /**
     * Indicate that the topic is required.
     */
    public function required(): static
    {
        return $this->state(fn (array $attributes) => [
            'required' => true,
        ]);
    }

    /**
     * Indicate that the topic is optional.
     */
    public function optional(): static
    {
        return $this->state(fn (array $attributes) => [
            'required' => false,
        ]);
    }

    /**
     * Set specific estimated minutes.
     */
    public function withDuration(int $minutes): static
    {
        return $this->state(fn (array $attributes) => [
            'estimated_minutes' => $minutes,
        ]);
    }

    /**
     * Set prerequisites for this topic.
     */
    public function withPrerequisites(array $topicIds): static
    {
        return $this->state(fn (array $attributes) => [
            'prerequisites' => $topicIds,
        ]);
    }
}
