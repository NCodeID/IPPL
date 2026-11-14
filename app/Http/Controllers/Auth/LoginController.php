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
| Desc : melakukan validasi email dan password jika benar maka sesi (cookie) dibuat
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
        description: 'Melakukan validasi email dan password. Jika benar, membuat sesi dan mengembalikan data user.',
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
                        new OA\Property(property: 'user', ref: '#/components/schemas/User', description: 'role berupa string nama role'),
                        new OA\Property(property: 'role', ref: '#/components/schemas/Role'),
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

        $request->session()->regenerate();

        $user = User::with('role')->where('email', $request->email)->first();

        return response()->json([
            'user' => [...$user->withoutRelations()->toArray(), 'role' => $user->role?->name],
            'role' => $user->role,
        ]);
    }
}
