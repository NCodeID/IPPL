<?php

namespace App\Http\Controllers\Rbac;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\Request;

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
    
    public function show(Permission $permission)
    {
        return response()->json($permission->load('roles'));
    }
}
