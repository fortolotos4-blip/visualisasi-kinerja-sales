<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\Sales;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
{
    $request->validate([
        'name' => 'required',
        'email' => 'required|email|unique:users',
        'password' => 'required|min:6',
        'jabatan' => 'required|in:admin,sales,manajer',
    ]);

    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
        'jabatan' => $request->jabatan,
    ]);

    if ($user->jabatan === 'sales') {
        Sales::create([
            'user_id' => $user->id,
            'kode_sales' => 'SA' . str_pad($user->id, 3, '0', STR_PAD_LEFT), // misalnya SA005
            'nama_sales' => $user->name,
            'wilayah_id' => null, // default, atau kamu bisa ambil dari form jika dinamis
            'target_penjualan' => 0, // default, atau bisa pakai input juga
        ]);
    }

    return redirect('/login')->with('success', 'Akun berhasil dibuat!');
}


    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
{
    $credentials = $request->only('email', 'password');

    if (Auth::attempt($credentials)) {
        $request->session()->regenerate();

        $user = Auth::user();

        //  Redirect sesuai jabatan
        switch ($user->jabatan) {
            case 'admin':
                return redirect('/dashboard/admin');
            case 'manajer':
                return redirect('/dashboard/manajer');
            case 'sales':
                return redirect('/dashboard/sales');
            default:
                Auth::logout(); // logout kalau jabatan tidak dikenal
                return redirect('/login')->with('error', 'Jabatan tidak dikenali.');
        }
    }

    return back()->with('error', 'Email atau password salah.');
}

    public function logout()
    {
        Auth::logout();
        return redirect('/login');
    }
    public function dashboard()
{
    $user = Auth::user();

    if ($user->jabatan === 'admin') {
        return view('dashboard-admin');
    } elseif ($user->jabatan === 'manajer') {
        return view('dashboard-manajer');
    } elseif ($user->jabatan === 'sales') {
        return view('dashboard-sales');
    } else {
        abort(403, 'Unauthorized');
    }
}

}
