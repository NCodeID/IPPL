<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/*
|
| Nama Fitur : Permission Authorization Middleware (PermissionMiddleware)
| Desc : Memeriksa apakah pengguna yang sedang login memiliki hak akses (permission) yang sesuai sebelum diperbolehkan mengakses route/endpoint tertentu.
| Param:
| @request: Illuminate\Http\Request (Request HTTP yang sedang berjalan)
| @next: Closure (Callback untuk melanjutkannya ke middleware/controller berikutnya)
| @permissions: string (Daftar nama permission yang diizinkan, dikirim melalui parameter middleware)
|
*/
class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasPermissionTo($permissions)) {
            return response()->json(['message' => 'Tidak punya akses.'], 403);
        }

        return $next($request);
    }
}
