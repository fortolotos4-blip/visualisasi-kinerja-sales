<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Wilayah;
class WilayahController extends Controller
{
    //Menampilkan daftar wilayah dengan pagination dan pencarian

    public function index(Request $request)
{
    $query = Wilayah::query();

    if ($request->has('search') && $request->search != '') {
        $query->where('nama_wilayah', 'like', '%' . $request->search . '%');
        $query->orWhere('kode_wilayah', 'like', '%' . $request->search . '%');
    }

    $wilayah = $query->orderBy('created_at','desc')->paginate(3)->withQueryString();



    return view('master.wilayah.index', compact('wilayah'));
}

    //Menampilkan form tambah wilayah
    public function create()
    {
        return view('master.wilayah.create');
    }

    // Menyimpan data wilayah
    public function store(Request $request)
    {
        $request->validate([
            'kode_wilayah' => 'required|unique:wilayah',
            'nama_wilayah' => 'required'
        ]);

        Wilayah::create($request->all());

        return redirect()->route('wilayah.index')->with('success', 'Wilayah berhasil ditambahkan.');
    }

    // Form untuk mengubah data wilayah
    public function edit(Request $request, $id)
    {
        $wilayah = Wilayah::findOrFail($id);

        $page = $request->input('page', 1);

        return view('master.wilayah.edit', compact('wilayah','page'));
    }

    // Memperbarui data wilayah
    public function update(Request $request, $id)
    {
        $request->validate([
            'nama_wilayah' => 'required'
        ]);

        $wilayah = Wilayah::findOrFail($id);
        $wilayah->update($request->all());

        $page = $request->input('page', 1);

        return redirect()->route('wilayah.index', ['page' => $page])
                         ->with('success', 'Wilayah berhasil diperbarui.');
    }

    // Menghapus data wilayah
    public function destroy(Request $request, $id)
    {
        $wilayah = Wilayah::findOrFail($id);
        $wilayah->delete();

        $page = $request->input('page', 1);

        return redirect()->route('wilayah.index', ['page' => $page])
                         ->with('success', 'Wilayah berhasil dihapus.');
    }
}
