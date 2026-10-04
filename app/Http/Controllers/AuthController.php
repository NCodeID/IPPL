<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * @group User Management
 *
 * Khusus Buat User Login dan Logout .
 */

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            "email" => "required|email",
            "password" => "required|string",
        ]);

        $user = User::where("email", $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                "email" => ["Kredensial yang Anda masukkan salah."],
            ]);
        }

        $user->tokens()->delete();

        $token = $user->createToken("pos_token", [$user->role])->plainTextToken;

        return response()->json(
            [
                "status" => "success",
                "message" => "Login berhasil",
                "data" => [
                    "access_token" => $token,
                    "token_type" => "Bearer",
                    "role" => $user->role,
                    "user" => [
                        "name" => $user->name,
                        "email" => $user->email,
                    ],
                ],
            ],
            200,
        );
    }

    public function logout(Request $request)
    {
        // Revoke token yang sedang digunakan saat ini
        $request->user()->currentAccessToken()->delete();

        return response()->json(
            [
                "status" => "success",
                "message" => "Logout berhasil",
            ],
            200,
        );
    }
}
