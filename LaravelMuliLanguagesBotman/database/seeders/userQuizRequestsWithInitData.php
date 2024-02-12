<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class userQuizRequestsWithInitData extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \DB::table('user_quiz_requests')->delete();

        \DB::table('user_quiz_requests')->insert([
            [
                'id'               => 1,
                'quiz_category_id' => 1, // 'Laravel development knowledge'
                'user_name'        => 'John Doe',
                'user_email'       => 'john_doe@site.com',
                'is_passed'        => false,
                'expires_at'       => Carbon::now(config('app.timezone'))->addDays(8),
                'hashed_link'      => (string) Str::uuid(),
                'created_at'       => Carbon::now(config('app.timezone')),
            ],
            [
                'id'               => 2,
                'quiz_category_id' => 1, // 'Laravel development knowledge'
                'user_name'        => 'Jane Doe',
                'user_email'       => 'jane_doe@site.com',
                'is_passed'        => false,
                'expires_at'       => Carbon::now(config('app.timezone'))->addDays(3),
                'hashed_link'      => (string) Str::uuid(),
                'created_at'       => Carbon::now(config('app.timezone')),
            ],
            [
                'id'               => 3,
                'quiz_category_id' => 1,  // 'Laravel development knowledge'
                'user_name'        => 'Judy Doe',
                'user_email'       => 'judy_doe@site.com',
                'is_passed'        => false,
                'expires_at'       => Carbon::now(config('app.timezone'))->addDays(1),
                'hashed_link'      => (string) Str::uuid(),
                'created_at'       => Carbon::now(config('app.timezone')),
            ],


            [
                'id'               => 4,
                'quiz_category_id' => 2, // 'Vuejs development knowledge'
                'user_name'        => 'John Doe',
                'user_email'       => 'john_doe@site.com',
                'is_passed'        => false,
                'expires_at'       => Carbon::now(config('app.timezone'))->addDays(8),
                'hashed_link'      => (string) Str::uuid(),
                'created_at'       => Carbon::now(config('app.timezone')),
            ],
            [
                'id'               => 5,
                'quiz_category_id' => 2, // 'Vuejs development knowledge'
                'user_name'        => 'Jane Doe',
                'user_email'       => 'jane_doe@site.com',
                'is_passed'        => false,
                'expires_at'       => Carbon::now(config('app.timezone'))->addDays(3),
                'hashed_link'      => (string) Str::uuid(),
                'created_at'       => Carbon::now(config('app.timezone')),
            ],
            [
                'id'               => 6, // 'Vuejs development knowledge'
                'quiz_category_id' => 2,
                'user_name'        => 'Judy Doe',
                'user_email'       => 'judy_doe@site.com',
                'is_passed'        => false,
                'expires_at'       => Carbon::now(config('app.timezone'))->addDays(1),
                'hashed_link'      => (string) Str::uuid(),
                'created_at'       => Carbon::now(config('app.timezone')),
            ],



        ]);

    }
}
