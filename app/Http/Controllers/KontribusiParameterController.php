<?php

namespace App\Http\Controllers;

use App\KontribusiParameter;
use Illuminate\Http\Request;
use Carbon\Carbon;

class KontribusiParameterController extends Controller
{
    // 🧩 INDEX - tampilkan per tahun saja
    public function index(Request $request)
    {
        $query = KontribusiParameter::selectRaw('periode_tahun, 
                MAX(bobot_kunjungan) as bobot_kunjungan,
                MAX(bobot_penawaran) as bobot_penawaran,
                MAX(bobot_penjualan) as bobot_penjualan,
                MAX(target_kunjungan) as target_kunjungan,
                MAX(target_penawaran) as target_penawaran,
                MAX(status) as status')
            ->groupBy('periode_tahun')
            ->orderBy('periode_tahun', 'desc');

        if ($request->filled('periode_tahun')) {
            $query->where('periode_tahun', $request->periode_tahun);
        }

        $parameters = $query->paginate(50)->withQueryString();

        return view('kontribusi_parameters.index', compact('parameters'));
    }

    // 🧩 FORM CREATE
    public function create()
    {
        return view('kontribusi_parameters.create');
    }

    // 🧩 SIMPAN DATA BARU UNTUK 12 BULAN
    public function store(Request $request)
    {
        $request->validate([
            'periode_tahun'     => 'required|integer|min:2020',
            'bobot_kunjungan'   => 'required|numeric',
            'bobot_penawaran'   => 'required|numeric',
            'bobot_penjualan'   => 'required|numeric',
            'target_kunjungan'  => 'required|integer',
            'target_penawaran'  => 'required|integer',
        ]);

        // Cek duplikasi tahun
        if (KontribusiParameter::where('periode_tahun', $request->periode_tahun)->exists()) {
            return back()->withErrors('Parameter untuk tahun ini sudah ditetapkan.');
        }

        // Simpan selama 12 bulan
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            KontribusiParameter::create([
                'manager_id'       => auth()->id(),
                'periode_bulan'    => str_pad($bulan, 2, '0', STR_PAD_LEFT),
                'periode_tahun'    => $request->periode_tahun,
                'bobot_kunjungan'  => $request->bobot_kunjungan,
                'bobot_penawaran'  => $request->bobot_penawaran,
                'bobot_penjualan'  => $request->bobot_penjualan,
                'target_kunjungan' => $request->target_kunjungan,
                'target_penawaran' => $request->target_penawaran,
                'status'           => 'Aktif',
            ]);
        }

        return redirect()->route('kontribusi_parameters.index')
            ->with('success', 'Parameter berhasil ditambahkan.');
    }

    // 🧩 FORM EDIT BERDASARKAN TAHUN
    public function edit(Request $request, $tahun)
    {
        $parameter = KontribusiParameter::where('periode_tahun', $tahun)->firstOrFail();
        $page = $request->input('page', 1);

        return view('kontribusi_parameters.edit', compact('parameter', 'page'));
    }

    public function batal($periode_tahun)
    {

        KontribusiParameter::where('periode_tahun', $periode_tahun)->update(['status' => 'Nonaktif']);
    
        return back()->with('success', 'Indikator Penilaian berhasil Dibatalkan.');
    }

    // 🧩 UPDATE SEMUA BULAN DALAM 1 TAHUN
    public function update(Request $request, $tahun)
    {
        $request->validate([
            'periode_tahun'     => 'required|integer|min:2020',
            'bobot_kunjungan'   => 'required|numeric',
            'bobot_penawaran'   => 'required|numeric',
            'bobot_penjualan'   => 'required|numeric',
            'target_kunjungan'  => 'required|integer',
            'target_penawaran'  => 'required|integer',
        ]);

        KontribusiParameter::where('periode_tahun', $tahun)->update([
            'periode_tahun'    => $request->periode_tahun,
            'bobot_kunjungan'  => $request->bobot_kunjungan,
            'bobot_penawaran'  => $request->bobot_penawaran,
            'bobot_penjualan'  => $request->bobot_penjualan,
            'target_kunjungan' => $request->target_kunjungan,
            'target_penawaran' => $request->target_penawaran,
        ]);

        return redirect()->route('kontribusi_parameters.index')
            ->with('success', 'Parameter berhasil diperbarui.');
    }

    // 🧩 HAPUS SEMUA BULAN DALAM 1 TAHUN
    public function destroy(Request $request, $tahun)
    {
        KontribusiParameter::where('periode_tahun', $tahun)->delete();

        return redirect()->route('kontribusi_parameters.index')
            ->with('success', 'Parameter berhasil dihapus.');
    }
}
