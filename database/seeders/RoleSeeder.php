<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['slug' => 'super_admin', 'name' => 'Super Admin'],
            ['slug' => 'user', 'name' => 'User'],
            ['slug' => 'qatu', 'name' => 'QATU'],
            ['slug' => 'director', 'name' => 'Director'],
        ] as $role) {
            Role::updateOrCreate(
                ['slug' => $role['slug']],
                ['name' => $role['name']]
            );
        }
    }
}
