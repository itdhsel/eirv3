<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.eirv3-login'); 
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'nokp' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        // 1. Query the external elatihanv3 database
        $externalUser = DB::connection('elatihan')
            ->table('peribadi')
            ->where('nokp', $request->nokp)
            ->first();

        // 2. Verify the user exists and the plaintext password matches
        if (! $externalUser || $externalUser->pwd !== $request->password) {
            throw ValidationException::withMessages([
                'nokp' => 'Invalid No. KP or password.',
            ]);
        }

        // 3. Sync the data to your local database
        $localUser = User::updateOrCreate(
            ['nokp' => $externalUser->nokp],
            [
                'name' => $externalUser->namapegawai ?? 'Unknown', 
                'jabatan' => $externalUser->jabatan,
                'password' => Hash::make($request->password),
            ]
        );

        // 4. Assign the default 'user' role if they don't have one
        if (! $localUser->role_id) {
            $userRole = Role::where('slug', 'user')->first();
            if ($userRole) {
                $localUser->update(['role_id' => $userRole->id]);
            }
        }

        // 5. Log the user in
        Auth::login($localUser);
        $request->session()->regenerate();
        if (! $request->user()->role_id) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            throw ValidationException::withMessages([
                'nokp' => 'Your account requires a role assignment. Contact the administrator.',
            ]);
        }

        return redirect()->intended('/');
        }

        public function destroy(Request $request): RedirectResponse
        {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
        }
}