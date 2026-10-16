<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/*
|
| Nama Fitur : Role Authorization Middleware (RoleMiddleware)
| Desc : Memeriksa apakah pengguna yang sedang login memiliki peran (role) yang sesuai sebelum diperbolehkan mengakses route/endpoint tertentu.
| Param:
| @request: Illuminate\Http\Request (Request HTTP yang sedang berjalan)
| @next: Closure (Callback untuk melanjutkannya ke middleware/controller berikutnya)
| @roles: string (Daftar nama role yang diizinkan, dikirim melalui parameter middleware)
|
*/
class RoleMiddleware
{
    public function handle(
        Request $request,
        Closure $next,
        string ...$roles,
    ): Response {
        $user = $request->user();

        if (!$user || !$user->hasRole($roles)) {
            return response()->json(["message" => "Tidak punya akses"], 403);
        }

        return $next($request);
    }
}
