<?php

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\User;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class TaskFactory extends Factory
{
    //   laravel factory definition with parameter

    public function definition()
    {
        $completed_at = null;
        $status = $this->faker->randomElement(array_keys(TaskStatus::getStatusSelectionItems()));
        if ($status === TaskStatus::DONE->value) { // If Task is DONE it must have completed_at filled
            $completed_at = $this->faker->dateTimeBetween('-1 week', '-1 minute');
        }
        $userId = $this->faker->randomElement(User::all())['id'];

        return [
            'user_id' => $userId,
            'title' => 'Task ' . fake()->unique()->name(),
            'priority' => $this->faker->randomElement(array_keys(TaskPriority::getPrioritySelectionItems())),
            'status' => $status,
            'description' => $this->faker->paragraphs(rand(1, 4), true),
            'completed_at' => $completed_at,
        ];
    }

    /**
     * Indicate that the task is completed.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function completed()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => TaskStatus::DONE->value,
            ];
        });
    }

    /**
     * Indicate that the task is incompleted.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function incompleted()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => TaskStatus::TODO->value,
            ];
        });
    }

    /**
     * Define which user created the task
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function userId($userId)
    {
        return $this->state(function (array $attributes) use ($userId) {
            return [
                'user_id' => $userId,
            ];
        });
    }

    public function configure()
    {
        return $this->afterCreating(function (Task $task) {
            $tasks = Task::all();

            if ($tasks->count() > 0 && rand(1, 3) >= 2) { // 2/3 of tasks would have filled parent_id
                $parent = $tasks->random();
                $task->update(['parent_id' => $parent->id]);
            }
        });
    }
}
