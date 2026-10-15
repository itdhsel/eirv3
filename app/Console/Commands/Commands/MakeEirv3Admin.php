<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class MakeEirv3Admin extends Command
{
    protected $signature = 'eirv3:make-admin';
    protected $description = 'Create the initial authorized eIRv3 Super Admin account';

    public function handle(): int
    {
        $role = Role::query()->where('slug', 'super_admin')->first();
        if (! $role) {
            $this->error('Super Admin role missing. Seed roles after reviewing the database.');
            return self::FAILURE;
        }

        $name = (string) $this->ask('Administrator name');
        $email = (string) $this->ask('Administrator email');
        $password = (string) $this->secret('Password (minimum 12 characters)');
        $validation = Validator::make(compact('name','email','password'), [
            'name' => ['required','string','max:255'],
            'email' => ['required','email','max:255','unique:users,email'],
            'password' => ['required','string','min:12'],
        ]);
        if ($validation->fails()) {
            foreach ($validation->errors()->all() as $error) $this->error($error);
            return self::FAILURE;
        }

        $user = new User();
        $user->name = $name;
        $user->email = $email;
        $user->password = Hash::make($password);
        $user->role_id = $role->id;
        $user->save();
        $this->info('Super Admin created. Keep credentials private.');
        return self::SUCCESS;
    }
}
