<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'telegram_id' => fake()->unique()->numberBetween(10_000_000, 9_999_999_999),
            'telegram_username' => fake()->userName(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'timezone' => 'Asia/Tashkent',
            'onboarding_step' => User::ONBOARDING_DONE,
        ];
    }

    public function onboarding(string $step = 'name'): static
    {
        return $this->state(fn () => ['onboarding_step' => $step]);
    }
}
