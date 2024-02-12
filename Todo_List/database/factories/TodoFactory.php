<?php


namespace Database\Factories;

use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TodoFactory extends Factory
{
    protected $model = Todo::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $name      = 'Todo List ' . str_replace('.', '', $this->faker->unique()->text(25));

        $completed = false;
        $rand   = rand(1, 5);

        if ($rand == 1) {
            $completed = true;
        }

        return [
            'user_id'        => $this->faker->randomElement(User::all())['id'],
            'name'        => $name,
            'completed'      => $completed,
            'description' => $this->faker->paragraph(),
        ];

    }


}
