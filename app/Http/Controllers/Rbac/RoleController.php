<?php

namespace App\Http\Controllers\Rbac;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;

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
    public function destroy(Role $role)
    {
        $role->permissions()->detach();
        $role->delete();

        return response()->json(null, 204);
    }
}
