<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Team;
use DB;
use Illuminate\Support\Facades\Hash;
use App\Actions\Fortify\CreateNewUser;


class UsersTableSeeder extends Seeder
{

    public function run()
    {

        app(CreateNewUser::class)->create([
            'id'         => 1,
            'name'       => 'John Doe',
            'email'      => 'john_doe@site.com',
            'password'   => '11111111',
            'email_verified_at' => '2019-04-29 11:03:50',
            'created_at' => '2019-04-29 11:03:50',
        ], true, [ACCESS_APP_EMPLOYEE_LABEL]);

        app(CreateNewUser::class)->create([
            'id'         => 2,
            'name'       => 'Jane Doe',
            'email'      => 'jane_doe@site.com',
            'password'   => '11111111',
            'email_verified_at' => '2019-04-29 11:03:50',
            'created_at' => '2019-04-29 11:03:50',
        ], true, [ACCESS_APP_EMPLOYEE_LABEL]);

        app(CreateNewUser::class)->create([
            'id'         => 3,
            'name'       => 'Admin',
            'email'      => 'admin@site.com',
            'password'   => '11111111',
            'email_verified_at' => '2019-04-29 11:03:50',
            'created_at' => '2019-04-29 11:03:50',
        ], true, [ACCESS_APP_ADMIN_LABEL, ACCESS_APP_EMPLOYEE_LABEL]);

        app(CreateNewUser::class)->create([
            'id'         => 4,
            'name'       => 'Manager',
            'email'      => 'manager@site.com',
            'password'   => '11111111',
            'email_verified_at' => '2019-04-29 11:03:50',
            'created_at' => '2019-04-29 11:03:50',
        ], true, [ACCESS_APP_MANAGER_LABEL, ACCESS_APP_EMPLOYEE_LABEL]);


    }
}

