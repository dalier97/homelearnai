<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subject>
 */
class SubjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement([
                'Mathematics', 'Science', 'Reading/Language Arts', 'History',
                'Geography', 'Art', 'Music', 'Physical Education', 'Spanish',
            ]),
            'color' => $this->faker->randomElement([
                '#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FECA57',
                '#FF9FF3', '#54A0FF', '#5F27CD', '#00D2D3', '#FF9F43',
            ]),
            'user_id' => \App\Models\User::factory(),
            'child_id' => \App\Models\Child::factory(),
        ];
    }
}
