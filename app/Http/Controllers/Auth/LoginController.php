<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            "email" => "required|email",
            "password" => "required",
        ]);

        if (!Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                "email" => ["credensial tidak valid."],
            ]);
        }

        $user = User::where("email", $request->email)->first();

        return response()->json([
            "user" => $user,
            "token" => $user->createToken("auth_token")->plainTextToken,
        ]);
    }
}
