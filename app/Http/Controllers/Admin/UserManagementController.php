<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', [
            'users' => User::query()->orderBy('name')->paginate(20),
            'roleNames' => Role::query()->pluck('name', 'id'),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create', [
            'roles' => Role::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'role_id' => ['required', 'integer', Rule::exists('roles', 'id')],
            'password' => ['required', 'confirmed', Password::min(12)],
        ]);

        $user = new User();
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->role_id = $data['role_id'];
        $user->password = Hash::make($data['password']);
        $user->save();

        return redirect()->route('admin.users.index')
            ->with('status', 'User account created successfully.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'user' => $user,
            'roles' => Role::query()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'role_id' => ['required', 'integer', Rule::exists('roles', 'id')],
        ]);

        $requestedRoleId = (int) $data['role_id'];
        if ($user->is($request->user()) && $requestedRoleId !== (int) $user->role_id) {
            throw ValidationException::withMessages([
                'role_id' => 'You cannot change your own role.',
            ]);
        }

        $superAdminId = Role::query()->where('slug', 'super_admin')->value('id');
        if ($superAdminId && (int) $user->role_id === (int) $superAdminId
            && $requestedRoleId !== (int) $superAdminId
            && User::query()->where('role_id', $superAdminId)->count() <= 1) {
            throw ValidationException::withMessages([
                'role_id' => 'At least one Super Admin must remain.',
            ]);
        }

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->role_id = $requestedRoleId;
        $user->save();

        return redirect()->route('admin.users.index')
            ->with('status', 'User information updated successfully.');
    }
}
