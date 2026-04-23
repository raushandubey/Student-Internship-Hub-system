<?php

namespace Database\Factories;

use App\Models\RecruiterProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RecruiterProfileFactory extends Factory
{
    protected $model = RecruiterProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'organization' => $this->faker->company(),
            'description' => $this->faker->paragraph(),
            'website' => $this->faker->url(),
            'logo_path' => null,
            'approval_status' => 'pending',
            'approved_by' => null,
            'approved_at' => null,
            'suspended_at' => null,
            'suspension_reason' => null,
            'rejection_reason' => null,
        ];
    }

    /**
     * Indicate that the recruiter profile is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'approval_status' => 'approved',
            'approved_by' => User::factory(),
            'approved_at' => now(),
        ]);
    }

    /**
     * Indicate that the recruiter profile is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'approval_status' => 'rejected',
            'rejection_reason' => $this->faker->sentence(),
        ]);
    }

    /**
     * Indicate that the recruiter profile is suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'approval_status' => 'suspended',
            'suspended_at' => now(),
            'suspension_reason' => $this->faker->sentence(),
        ]);
    }
}
