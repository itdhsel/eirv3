<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class InitialUsersSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'super_admin' => 'Super Admin',
            'user'        => 'User',
            'qatu'        => 'QATU',
            'director'    => 'Director',
        ];

        foreach ($roles as $slug => $roleName) {

            $this->command->info(
                "Creating account for {$roleName}"
            );

            $name = trim((string) $this->command->ask(
                "Full name"
            ));

            $email = trim((string) $this->command->ask(
                "Email address"
            ));

            if (! $name || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \RuntimeException(
                    "A valid name and email are required."
                );
            }

            if (User::where('email', $email)->exists()) {
                $this->command->warn(
                    "Account already exists. Skipping {$email}."
                );
                continue;
            }

            $password = (string) $this->command->secret(
                "Password (minimum 12 characters)"
            );

            if (strlen($password) < 12) {
                throw new \RuntimeException(
                    "Password must be at least 12 characters."
                );
            }

            $user = new User();
            $user->name = $name;
            $user->email = $email;
            $user->password = Hash::make($password);
            $user->role_id = Role::where(
                'slug', $slug
            )->firstOrFail()->id;

            $user->save();

            $this->command->info(
                "{$roleName} account created."
            );
        }
    }
}
