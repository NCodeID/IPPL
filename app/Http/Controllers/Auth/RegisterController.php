<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/*
|
| Nama Fitur : Logout
| Desc : Menghapus token sehingga pengguna diarahkan kehalaman login / landing page
| Param:
| @name: string
| @email: string
| @password: string
| @role_id: tinyint / boolean
|
*/

class RegisterController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|confirmed|min:8',
            'role_id' => 'nullable|exists:roles,id',
        ]);

        $role = Role::where('name', 'kasir')->first();

        if (! $role) {
            throw ValidationException::withMessages([
                'role' => ['No default role configured.'],
            ]);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $request->input('role_id', $role->id),
            'is_active' => true,
        ]);

        return response()->json([
            'user' => $user->load('role'),
            'token' => $user->createToken('auth_token')->plainTextToken,
        ], 201);
    }
}
