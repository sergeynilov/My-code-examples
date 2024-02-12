<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Article;

class articleVotesWithInitData extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach(Article::all() as $article) {
            $votes = \App\Models\Vote::inRandomOrder()->take(rand(1, 3))->pluck('id');
            $article->votes()->attach($votes);
        }

    }
}
