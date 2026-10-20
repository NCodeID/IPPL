<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

/*
|
| Nama Fitur : Login
| Desc : melakukan validasi email dan password jika benar maka dapat token
| Param:
| @email: string
| @password: string
|
*/
class LoginController extends Controller
{
    #[OA\Post(
        path: '/api/login',
        operationId: 'authLogin',
        summary: 'Login',
        description: 'Melakukan validasi email dan password. Jika benar, mengembalikan data user beserta token.',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password'),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login berhasil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'user', ref: '#/components/schemas/User'),
                        new OA\Property(property: 'token', type: 'string', example: '1|abc123def456'),
                    ],
                ),
            ),
            new OA\Response(response: 422, description: 'Kredensial tidak valid'),
        ],
    )]
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['credensial tidak valid.'],
            ]);
        }

        $user = User::where('email', $request->email)->first();

        return response()->json([
            'user' => $user,
            'token' => $user->createToken('auth_token')->plainTextToken,
        ]);
    }
}
