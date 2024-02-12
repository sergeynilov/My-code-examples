<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class usersWithInitData extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->count(10)->create();
    }
}