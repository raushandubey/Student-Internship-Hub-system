<?php

namespace Database\Factories;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProfileFactory extends Factory
{
    protected $model = Profile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->name(),
            'academic_background' => $this->faker->sentence(),
            'skills' => ['PHP', 'Laravel', 'JavaScript', 'MySQL'],
            'career_interests' => $this->faker->paragraph(),
            'resume_path' => '/resumes/' . $this->faker->uuid() . '.pdf',
            'aadhaar_number' => $this->faker->numerify('############'),
        ];
    }
}
