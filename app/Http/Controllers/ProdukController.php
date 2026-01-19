<?php

namespace App\Http\Controllers;

use App\Produk;
use Illuminate\Http\Request;

class ProdukController extends Controller
{
    // Menampilkan daftar produk dengan pagination dan pencarian
    public function index(Request $request)
    {
    $query = Produk::query();

    if ($request->has('search') && $request->search != '') {
        $query->where('nama_produk', 'like', '%' . $request->search . '%');
        $query->orWhere('kode_produk', 'like', '%' . $request->search . '%');
    }

    $produk = $query->orderBy('created_at', 'desc')->paginate(3)->withQueryString();

    return view('master.produk.index', compact('produk'));
    }

    // Form untuk menambahkan produk baru
    public function create()
    {
        return view('master.produk.create');
    }

    // Menyimpan data produk baru
    public function store(Request $request)
    {
        $request->validate([
            'kode_produk' => 'required|unique:produk',
            'nama_produk' => 'required',
            'harga' => 'required|numeric'
        ]);

        Produk::create($request->all());

        return redirect()->route('produk.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    // Form untuk mengubah data produk
    public function edit(Request $request, $id)
    {
        $produk = Produk::findOrFail($id);

        $page = $request->input('page', 1);

        return view('master.produk.edit', compact('produk', 'page'));
    }

    // Memperbarui data produk
    public function update(Request $request, $id)
    {
        $request->validate([
            'nama_produk' => 'required',
            'harga' => 'required|numeric'
        ]);

        $produk = Produk::findOrFail($id);
        $produk->update($request->all());

        $page = $request->input('page', 1);

        return redirect()->route('produk.index', ['page' => $page])
                         ->with('success', 'Produk berhasil diperbarui.');
    }

    // Menghapus data produk
    public function destroy(Request $request, $id)
    {
        $produk = Produk::findOrFail($id);
        $produk->delete();

        $page = $request->input('page', 1);

        return redirect()->route('produk.index', ['page' => $page])
                         ->with('success', 'Produk berhasil dihapus.');
    }
}
