<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Planner\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(3),
            'date' => now('Asia/Tashkent')->toDateString(),
        ];
    }

    public function done(): static
    {
        return $this->state(fn () => ['done_at' => now()]);
    }
}
