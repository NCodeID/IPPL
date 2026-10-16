<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;


/*
|
| Nama Fitur : Logout
| Desc : Menghapus token sehingga pengguna diarahkan kehalaman login / landing page
| 
*/
class LogoutController extends Controller
{
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Proses Logout berhasil.',
        ]);
    }
}
