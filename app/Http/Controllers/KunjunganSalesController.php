<?php

namespace App\Http\Controllers;
use App\KunjunganSales;
use App\Sales;
use App\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class KunjunganSalesController extends Controller
{
    // Menampilkan daftar kunjungan sales dengan pagination dan filter tanggal
    public function index(Request $request)
{
    $sales = Sales::where('user_id', Auth::id())->first();

    if (!$sales) {
        return redirect()->back()->with('error', 'Data sales tidak ditemukan.');
    }

    // Mulai query kunjungan
    $query = KunjunganSales::with('customer')
                ->where('sales_id', $sales->id);

    // Filter berdasarkan tanggal kunjungan
    if ($request->filled('tanggal')) {
        $query->whereDate('tanggal_kunjungan', $request->tanggal);
    }

    $kunjungan = $query->orderBy('tanggal_kunjungan', 'desc')->paginate(5);

    return view('kunjungan_sales.index', compact('kunjungan'));
}


    // Form input kunjungan
    public function create()
{
    if (Auth::user()->jabatan === 'admin') {
        $customers = Customer::all(); // admin bisa lihat semua customer
    } else {
        $sales = Sales::where('user_id', Auth::id())->first();
        $customers = Customer::where('user_id', Auth::id())
        ->orderByRaw("
            CASE 
                WHEN status_customer = 'baru' THEN 1
                WHEN status_customer = 'lama' THEN 2
                ELSE 3
            END
        ")
        ->orderBy('nama_customer')
        ->get();; // hanya customer milik sales tersebut
    }

    return view('kunjungan_sales.create', compact('customers'));
}


    // Simpan data kunjungan ke database
    public function store(Request $request)
    {
        $sales = Sales::where('user_id', Auth::id())->first();

        if (!$sales) {
            return redirect()->back()->withErrors('Sales tidak ditemukan.');
        }

        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'tanggal_kunjungan' => 'required|date',
            'tujuan' => 'required|string',
            'status' => 'required|in:Pending,Berhasil,Batal',
            'keterangan' => 'nullable|string'
        ]);

        KunjunganSales::create([
            'sales_id' => $sales->id,
            'customer_id' => $request->customer_id,
            'tanggal_kunjungan' => $request->tanggal_kunjungan,
            'tujuan' => $request->tujuan,
            'status' => $request->status,
            'keterangan' => $request->keterangan
        ]);

        return redirect()->route('kunjungan.index')->with('success', 'Data kunjungan berhasil disimpan.');
    }
}
