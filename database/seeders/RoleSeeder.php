<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role; 

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Super Administrator', 'slug' => 'superadmin'],
            ['name' => 'General User', 'slug' => 'user'],
            ['name' => 'Liaison Officer', 'slug' => 'lo'],
            ['name' => 'Head of Department', 'slug' => 'hod'],
            ['name' => 'Quality Assurance', 'slug' => 'qa'],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['slug' => $role['slug']], $role);
        }
    }
}