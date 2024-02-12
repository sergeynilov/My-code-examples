<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Models\Permission;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {

        Permission::create(['name' => ACCESS_APP_ADMIN_LABEL, 'guard_name' => 'web']);

        Permission::create(['name' => ACCESS_APP_MANAGER_LABEL, 'guard_name' => 'web']);

        Permission::create(['name' => ACCESS_APP_EMPLOYEE_LABEL,  'guard_name' => 'web']);

    }
}
