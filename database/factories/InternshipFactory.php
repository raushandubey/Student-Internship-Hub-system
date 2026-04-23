<?php

namespace Database\Factories;

use App\Models\Internship;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InternshipFactory extends Factory
{
    protected $model = Internship::class;

    public function definition(): array
    {
        return [
            'title' => fake()->jobTitle(),
            'organization' => fake()->company(),
            'required_skills' => ['PHP', 'Laravel', 'MySQL'],
            'duration' => fake()->randomElement(['3 months', '6 months', '12 months']),
            'location' => fake()->city(),
            'description' => fake()->paragraph(),
            'is_active' => true,
            'recruiter_id' => null,
        ];
    }

    public function forRecruiter(User $recruiter): static
    {
        return $this->state(fn (array $attributes) => [
            'recruiter_id' => $recruiter->id,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
