<?php

namespace App\Http\Controllers\Rbac;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class PermissionController extends Controller
{
    /*
    |
    | Nama Fitur : Menampilkan Daftar Permission (Index)
    | Desc : Mengambil dan menampilkan daftar data permission. Fitur ini mendukung pencarian berdasarkan nama dan pembatasan jumlah data per halaman (pagination).
    | Param:
    | @search: string (opsional, query parameter untuk pencarian nama)
    | @per_page: integer (opsional, query parameter untuk pagination, default: 15)
    |
    */
    #[OA\Get(
        path: '/api/permissions',
        operationId: 'permissionIndex',
        summary: 'Menampilkan daftar permission',
        description: 'Mengambil daftar permission. Mendukung pencarian nama dan pagination.',
        tags: ['Permissions'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(
                name: 'search',
                description: 'Pencarian berdasarkan nama permission',
                required: false,
                schema: new OA\Schema(type: 'string'),
            ),
            new OA\QueryParameter(
                name: 'per_page',
                description: 'Jumlah data per halaman (default: 15)',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 15),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar permission',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Permission'),
                        ),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Tidak terautentikasi'),
            new OA\Response(response: 403, description: 'Tidak memiliki permission manage_permissions'),
        ],
    )]
    public function index(Request $request)
    {
        $query = Permission::query();

        if ($request->has('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        $permissions = $query->paginate($request->get('per_page', 15));

        return response()->json($permissions);
    }
    
    /*
    |
    | Nama Fitur : Menampilkan Detail Permission (Show)
    | Desc : Mengambil data satu permission secara spesifik berdasarkan ID, sekaligus memuat (load) data relasi roles yang memiliki permission tersebut.
    | Param:
    | @permission: integer (ID dari permission yang dikirim melalui parameter URL/Route)
    |
    */
    #[OA\Get(
        path: '/api/permissions/{permission}',
        operationId: 'permissionShow',
        summary: 'Menampilkan detail permission',
        description: 'Mengambil satu permission berdasarkan ID beserta relasi roles yang memilikinya.',
        tags: ['Permissions'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(
                name: 'permission',
                description: 'ID dari permission',
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detail permission', content: new OA\JsonContent(ref: '#/components/schemas/Permission')),
            new OA\Response(response: 401, description: 'Tidak terautentikasi'),
            new OA\Response(response: 404, description: 'Permission tidak ditemukan'),
        ],
    )]
    public function show(Permission $permission)
    {
        return response()->json($permission->load('roles'));
    }
}
