<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Sales;
use App\Wilayah;
class SalesController extends Controller
{
    // Menampilkan daftar sales dengan pagination dan pencarian
    public function index(Request $request)
{
    $query = Sales::with('wilayah'); // ✅ INI YANG BENAR

    if ($request->filled('search')) {
        $query->where(function ($q) use ($request) {
            $q->where('nama_sales', 'like', '%' . $request->search . '%')
              ->orWhere('kode_sales', 'like', '%' . $request->search . '%');
        });
    }

    $sales = $query
        ->orderBy('created_at', 'desc')
        ->paginate(5)
        ->withQueryString();

    return view('master.sales.index', compact('sales'));
}

    // Form untuk menambahkan sales baru
    public function create()
    {
        $wilayah = Wilayah::all();
        return view('master.sales.create', compact('wilayah'));
    }

    // Menyimpan data sales baru
    public function store(Request $request)
    {
        $request->validate([
            'kode_sales' => 'required|unique:sales',
            'nama_sales' => 'required',
            'wilayah_id' => 'required|exists:wilayah,id',
            'target_penjualan' => 'required|numeric',
        ]);

        Sales::create($request->all());

        return redirect()->route('sales.index')->with('success', 'Sales berhasil ditambahkan.');
    }

    // Form untuk mengedit data sales
    public function edit(Request $request, $id)
    {
        $sales = Sales::findOrFail($id);
        $wilayah = Wilayah::all();

        $page = $request->input('page', 1);

        return view('master.sales.edit', compact('sales', 'wilayah', 'page'));
    }

    // Memperbarui data sales
    public function update(Request $request, $id)
    {
        $request->validate([
            'nama_sales' => 'required',
            'wilayah_id' => 'nullable|exists:wilayah,id',
            'target_penjualan' => 'required|numeric',
        ]);

        $sales = Sales::findOrFail($id);
        $sales->update($request->all());

        $page = $request->input('page', 1);

        return redirect()->route('sales.index', ['page' => $page])
                         ->with('success', 'Sales berhasil diperbarui.');

    }

    // Menghapus data sales 
    public function destroy(Request $request, $id)
    {
        $sales = Sales::findOrFail($id);
        $sales->delete();

        $page = $request->input('page', 1);

        return redirect()->route('sales.index', ['page' => $page])
                         ->with('success', 'Sales berhasil dihapus.');
    }
}
