<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/*
|
| Nama Fitur : Logout
| Desc : Menghapus token sehingga pengguna diarahkan kehalaman login / landing page
| 
*/
class LogoutController extends Controller
{
    #[OA\Post(
        path: '/api/logout',
        operationId: 'authLogout',
        summary: 'Logout',
        description: 'Menghapus token aktif sehingga pengguna diarahkan ke halaman login / landing page.',
        tags: ['Auth'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Proses logout berhasil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Proses Logout berhasil.'),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Tidak terautentikasi'),
        ],
    )]
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Proses Logout berhasil.',
        ]);
    }
}
