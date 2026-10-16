<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/*
|
| Nama Fitur : Active User Check Middleware (ActiveUserMiddleware)
| Desc : Memeriksa status keaktifan akun pengguna yang sedang login. Jika akun dalam status nonaktif (is_active = false), request akan ditolak dengan response JSON error 403 Forbidden.
| Param:
| @request: Illuminate\Http\Request (Request HTTP yang sedang berjalan)
| @next: Closure (Callback untuk melanjutkannya ke middleware/controller berikutnya)
|
*/
class ActiveUserMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            return response()->json(['message' => 'Akun tidak dapat dipakai.'], 403);
        }

        return $next($request);
    }
}
