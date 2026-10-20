<?php

namespace App\Http\Controllers\Rbac;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class RoleController extends Controller
{
    /*
    |
    | Nama Fitur : Menampilkan Daftar Role (Index)
    | Desc : Mengambil dan menampilkan daftar data role beserta relasi permission-nya. Fitur ini mendukung pencarian berdasarkan nama dan pembatasan jumlah data per halaman (pagination).
    | Param:
    | @search: string (opsional, query parameter untuk pencarian nama)
    | @per_page: integer (opsional, query parameter untuk pagination, default: 15)
    |
    */
    #[OA\Get(
        path: '/api/roles',
        operationId: 'roleIndex',
        summary: 'Menampilkan daftar role',
        description: 'Mengambil daftar role beserta relasi permission-nya. Mendukung pencarian nama dan pagination.',
        tags: ['Roles'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(
                name: 'search',
                description: 'Pencarian berdasarkan nama role',
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
                description: 'Daftar role',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Role'),
                        ),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Tidak terautentikasi'),
            new OA\Response(response: 403, description: 'Tidak memiliki permission manage_roles'),
        ],
    )]
    public function index(Request $request)
    {
        $query = Role::query();

        if ($request->has("search")) {
            $query->where("name", "like", "%" . $request->search . "%");
        }

        $roles = $query
            ->with("permissions")
            ->paginate($request->get("per_page", 15));

        return response()->json($roles);
    }
    /*
    |
    | Nama Fitur : Membuat Role Baru (Store)
    | Desc : Menyimpan data role baru ke dalam database. Memastikan nama role yang diinputkan bersifat unik (tidak duplikat).
    | Param:
    | @name: string (required, nama dari role baru)
    |
    */
    #[OA\Post(
        path: '/api/roles',
        operationId: 'roleStore',
        summary: 'Membuat role baru',
        description: 'Menyimpan data role baru ke dalam database. Nama role harus unik.',
        tags: ['Roles'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'manager'),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 201, description: 'Role berhasil dibuat', content: new OA\JsonContent(ref: '#/components/schemas/Role')),
            new OA\Response(response: 401, description: 'Tidak terautentikasi'),
            new OA\Response(response: 403, description: 'Tidak memiliki permission manage_roles'),
            new OA\Response(response: 422, description: 'Validasi gagal'),
        ],
    )]
    public function store(Request $request)
    {
        $request->validate([
            "name" => "required|string|max:255|unique:roles,name",
        ]);

        $role = Role::create(["name" => $request->name]);

        return response()->json($role, 201);
    }
    /*
    |
    | Nama Fitur : Menampilkan Detail Role (Show)
    | Desc : Mengambil data satu role secara spesifik berdasarkan ID, sekaligus memuat (load) data relasi permissions yang dimiliki role tersebut.
    | Param:
    | @role: integer (ID dari role yang dikirim melalui parameter URL/Route)
    |
    */
    #[OA\Get(
        path: '/api/roles/{role}',
        operationId: 'roleShow',
        summary: 'Menampilkan detail role',
        description: 'Mengambil satu role berdasarkan ID beserta relasi permissions-nya.',
        tags: ['Roles'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(
                name: 'role',
                description: 'ID dari role',
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detail role', content: new OA\JsonContent(ref: '#/components/schemas/Role')),
            new OA\Response(response: 401, description: 'Tidak terautentikasi'),
            new OA\Response(response: 404, description: 'Role tidak ditemukan'),
        ],
    )]
    public function show(Role $role)
    {
        return response()->json($role->load("permissions"));
    }
    /*
    |
    | Nama Fitur : Mengubah Data Role (Update)
    | Desc : Memperbarui nama role dan/atau menyinkronkan (sync) daftar permission yang terhubung dengan role tersebut.
    | Param:
    | @role: integer (ID dari role yang dikirim melalui parameter URL/Route)
    | @name: string (opsional, nama role yang baru)
    | @permissions: array of integer (opsional, kumpulan ID permission untuk disinkronisasi)
    |
    */
    #[OA\Put(
        path: '/api/roles/{role}',
        operationId: 'roleUpdate',
        summary: 'Mengubah data role',
        description: 'Memperbarui nama role dan/atau menyinkronkan (sync) daftar permission yang terhubung dengan role tersebut.',
        tags: ['Roles'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(
                name: 'role',
                description: 'ID dari role',
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'admin'),
                    new OA\Property(
                        property: 'permissions',
                        type: 'array',
                        items: new OA\Items(type: 'integer'),
                        example: [1, 2, 3],
                    ),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Role berhasil diperbarui', content: new OA\JsonContent(ref: '#/components/schemas/Role')),
            new OA\Response(response: 401, description: 'Tidak terautentikasi'),
            new OA\Response(response: 404, description: 'Role tidak ditemukan'),
            new OA\Response(response: 422, description: 'Validasi gagal'),
        ],
    )]
    public function update(Request $request, Role $role)
    {
        $request->validate([
            "name" => "sometimes|string|max:255|unique:roles,name," . $role->id,
            "permissions" => "sometimes|array",
            "permissions.*" => "exists:permissions,id",
        ]);

        if ($request->has("name")) {
            $role->update(["name" => $request->name]);
        }

        if ($request->has("permissions")) {
            $role->permissions()->sync($request->permissions);
        }

        return response()->json($role->load("permissions"));
    }
    /*
    |
    | Nama Fitur : Menghapus Role (Destroy)
    | Desc : Menghapus data role dari database. Fitur ini juga melepaskan (detach) seluruh relasi permission yang terkait secara otomatis sebelum role dihapus.
    | Param:
    | @role: integer (ID dari role yang dikirim melalui parameter URL/Route)
    |
    */
    #[OA\Delete(
        path: '/api/roles/{role}',
        operationId: 'roleDestroy',
        summary: 'Menghapus role',
        description: 'Menghapus data role dari database. Seluruh relasi permission terlepas (detach) otomatis sebelum role dihapus.',
        tags: ['Roles'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(
                name: 'role',
                description: 'ID dari role',
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Role berhasil dihapus'),
            new OA\Response(response: 401, description: 'Tidak terautentikasi'),
            new OA\Response(response: 404, description: 'Role tidak ditemukan'),
        ],
    )]
    public function destroy(Role $role)
    {
        $role->permissions()->detach();
        $role->delete();

        return response()->json(null, 204);
    }
}
