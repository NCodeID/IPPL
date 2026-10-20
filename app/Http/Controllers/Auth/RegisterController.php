<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

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
    #[OA\Post(
        path: '/api/register',
        operationId: 'authRegister',
        summary: 'Register user baru',
        description: 'Membuat user baru. Membutuhkan permission manage_users. Jika role_id tidak dikirim, defaults ke role kasir.',
        tags: ['Auth'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'password'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Budi'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'budi@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'password'),
                    new OA\Property(property: 'role_id', type: 'integer', nullable: true, example: 2),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'User berhasil dibuat',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'user', ref: '#/components/schemas/User'),
                        new OA\Property(property: 'token', type: 'string', example: '2|xyz789uvw'),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Tidak terautentikasi'),
            new OA\Response(response: 422, description: 'Validasi gagal'),
        ],
    )]
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
