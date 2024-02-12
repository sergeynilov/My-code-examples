<?php


namespace Database\Factories;

use App\Models\TodoTask;
use App\Models\Todo;
use Illuminate\Database\Eloquent\Factories\Factory;

class TodoTaskFactory extends Factory
{
    protected $model = TodoTask::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $name      = 'Task ' . str_replace('.', '', $this->faker->unique()->text(30));
        $status = 'U';
        $rand   = rand(1, 6);
        $deadline= $this->faker->dateTimeBetween($startDate = '-10 days', $endDate = '100 days', config('app.timezone'));

        if ($rand == 1) {
            $status = 'D';
        }
        if ($rand == 2) {
            $status = 'C';
        }

        return [
            'todo_id'        => $this->faker->randomElement(Todo::all())['id'],
            'name'        => $name,
            'description' => $this->faker->paragraph(),

            'deadline'      => $deadline,
            'status'      => $status,
        ];
    }

}
