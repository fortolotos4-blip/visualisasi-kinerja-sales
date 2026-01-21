<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class EnsureSalesActive
{
    public function handle($request, Closure $next)
    {
        $user = Auth::user();

        // Hanya berlaku untuk role sales
        if ($user && $user->jabatan === 'sales') {

            $sales = $user->sales;

            // ❌ Tidak punya relasi sales atau dinonaktifkan
            if (!$sales || !$sales->is_active) {
                Auth::logout();
                return redirect('/login')
                    ->withErrors(['Akun Anda sudah dinonaktifkan.']);
            }

            // ⚠️ Aktif tapi belum punya wilayah
            if (is_null($sales->wilayah_id)) {
                return redirect()->route('dashboard.sales')
                    ->with('error', 'Akun Anda belum dikonfigurasi oleh admin.');
            }
        }

        return $next($request);
    }
}
