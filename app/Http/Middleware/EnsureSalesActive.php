<?php

namespace App\Http\Middleware;

use Closure;

use Illuminate\Support\Facades\Auth;


class EnsureSalesActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
{
    $user = Auth::user();

    if ($user->jabatan === 'sales') {
        $sales = $user->sales;
        if ($sales && is_null($sales->wilayah_id)) {
            return redirect('/dashboard/sales')
                ->with('error', 'Akun Anda belum dikonfigurasi oleh admin.');
        }
    }

    return $next($request);
}

}
