<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $roles = [
            ['name' => 'super admin', 'guard_name' => 'web'],
            ['name' => 'admin', 'guard_name' => 'web'],
        ];

        Role::upsert($roles, ['name']);
    }
}
