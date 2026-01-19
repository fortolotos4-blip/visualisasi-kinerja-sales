<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use App\Penawaran;
use App\SalesOrder;
use App\Pembayaran;
use App\Sales;
use App\KunjunganSales;
use Illuminate\Support\Facades\Auth;

class LaporanController extends Controller
{
    // ====================== 1. LAPORAN SALES (PRIBADI) ======================
    public function laporanSales()
    {
        $user  = Auth::user();
        $sales = Sales::where('user_id', $user->id)->first();

        if (!$sales) {
            return redirect()->back()->withErrors('Data sales tidak ditemukan.');
        }

        // Rekap data berdasarkan sales_id
        $jumlahKunjungan = KunjunganSales::where('sales_id', $sales->id)
            ->where('status', 'Berhasil')
            ->count();

        $jumlahPenawaran = Penawaran::where('sales_id', $sales->id)
            ->where('status', 'diterima')
            ->count();

        $jumlahSO = SalesOrder::where('sales_id', $sales->id)->count();

        // 🔹 TOTAL PENJUALAN PAKAI HEADER: total_harga
        $totalPenjualan = SalesOrder::where('sales_id', $sales->id)
            ->sum('total_harga');

        // 🔹 TOTAL PEMBAYARAN (hanya yg diterima)
        $salesOrderIds   = SalesOrder::where('sales_id', $sales->id)->pluck('id');
        $totalPembayaran = Pembayaran::whereIn('sales_order_id', $salesOrderIds)
            ->where('status', 'diterima')
            ->sum('jumlah');

        // 🔹 PIUTANG tidak boleh minus
        $piutang = max($totalPenjualan - $totalPembayaran, 0);

        return view('laporan.sales', compact(
            'jumlahKunjungan',
            'jumlahPenawaran',
            'jumlahSO',
            'totalPenjualan',
            'totalPembayaran',
            'piutang'
        ));
    }

    // ====================== 2. LAPORAN ADMIN ======================
    public function laporanAdmin()
    {
        $bulan = request('bulan');
        $tahun = request('tahun');

        $semuaSales = Sales::with('user')->get();
        $data = [];

        foreach ($semuaSales as $sales) {
            // Kunjungan
            $jumlahKunjungan = KunjunganSales::where('sales_id', $sales->id)
                ->where('status', 'Berhasil')
                ->when($bulan && $tahun, function ($query) use ($bulan, $tahun) {
                    $query->whereMonth('tanggal_kunjungan', $bulan)
                          ->whereYear('tanggal_kunjungan', $tahun);
                })->count();

            // Penawaran
            $jumlahPenawaran = Penawaran::where('sales_id', $sales->id)
                ->where('status', 'diterima')
                ->when($bulan && $tahun, function ($query) use ($bulan, $tahun) {
                    $query->whereMonth('tanggal_penawaran', $bulan)
                          ->whereYear('tanggal_penawaran', $tahun);
                })->count();

            // Sales Order
            $querySO = SalesOrder::where('sales_id', $sales->id);
            if ($bulan && $tahun) {
                $querySO->whereMonth('tanggal', $bulan)
                        ->whereYear('tanggal', $tahun);
            }

            $jumlahSO = (clone $querySO)->count();

            // 🔹 TOTAL PENJUALAN: sum total_harga
            $totalPenjualan = (clone $querySO)->sum('total_harga');

            // 🔹 TOTAL PEMBAYARAN (hanya status diterima)
            $queryPembayaran = Pembayaran::whereHas('salesOrder', function ($q) use ($sales) {
                $q->where('sales_id', $sales->id);
            });

            if ($bulan && $tahun) {
                $queryPembayaran->whereMonth('tanggal_pembayaran', $bulan)
                                ->whereYear('tanggal_pembayaran', $tahun);
            }

            $totalPembayaran = $queryPembayaran
                ->where('status', 'diterima')
                ->sum('jumlah');

            // 🔹 PIUTANG tidak boleh minus
            $piutang = max($totalPenjualan - $totalPembayaran, 0);

            $data[] = [
                'nama'             => optional($sales->user)->name ?? '-',
                'jumlahKunjungan'  => $jumlahKunjungan,
                'jumlahPenawaran'  => $jumlahPenawaran,
                'jumlahSO'         => $jumlahSO,
                'totalPenjualan'   => $totalPenjualan,
                'totalPembayaran'  => $totalPembayaran,
                'piutang'          => $piutang,
            ];
        }

        return view('laporan.admin', compact('data', 'bulan', 'tahun'));
    }

    // ====================== 3. LAPORAN MANAGER ======================
    public function laporanManager()
    {
        $bulan = request('bulan');
        $tahun = request('tahun');

        $semuaSales = Sales::with('user')->get();
        $data = [];

        foreach ($semuaSales as $sales) {
            // Kunjungan
            $jumlahKunjungan = KunjunganSales::where('sales_id', $sales->id)
                ->where('status', 'Berhasil')
                ->when($bulan && $tahun, function ($query) use ($bulan, $tahun) {
                    $query->whereMonth('tanggal_kunjungan', $bulan)
                          ->whereYear('tanggal_kunjungan', $tahun);
                })->count();

            // Penawaran
            $jumlahPenawaran = Penawaran::where('sales_id', $sales->id)
                ->where('status', 'diterima')
                ->when($bulan && $tahun, function ($query) use ($bulan, $tahun) {
                    $query->whereMonth('tanggal_penawaran', $bulan)
                          ->whereYear('tanggal_penawaran', $tahun);
                })->count();

            // Sales Order
            $querySO = SalesOrder::where('sales_id', $sales->id);
            if ($bulan && $tahun) {
                $querySO->whereMonth('tanggal', $bulan)
                        ->whereYear('tanggal', $tahun);
            }

            $jumlahSO = (clone $querySO)->count();

            // 🔹 TOTAL PENJUALAN: sum total_harga
            $totalPenjualan = (clone $querySO)->sum('total_harga');

            // 🔹 TOTAL PEMBAYARAN (hanya status diterima)
            $queryPembayaran = Pembayaran::whereHas('salesOrder', function ($q) use ($sales) {
                $q->where('sales_id', $sales->id);
            });

            if ($bulan && $tahun) {
                $queryPembayaran->whereMonth('tanggal_pembayaran', $bulan)
                                ->whereYear('tanggal_pembayaran', $tahun);
            }

            $totalPembayaran = $queryPembayaran
                ->where('status', 'diterima')
                ->sum('jumlah');

            // 🔹 PIUTANG
            $piutang = max($totalPenjualan - $totalPembayaran, 0);

            $data[] = [
                'nama'             => optional($sales->user)->name ?? '-',
                'jumlahKunjungan'  => $jumlahKunjungan,
                'jumlahPenawaran'  => $jumlahPenawaran,
                'jumlahSO'         => $jumlahSO,
                'totalPenjualan'   => $totalPenjualan,
                'totalPembayaran'  => $totalPembayaran,
                'piutang'          => $piutang,
            ];
        }

        return view('laporan.manager', compact('data', 'bulan', 'tahun'));
    }

    // ====================== 4. EXPORT PDF ======================
    public function exportPDF(Request $request)
    {
        $bulan = $request->input('bulan', date('m'));
        $tahun = $request->input('tahun', date('Y'));

        $salesData = Sales::with('user')->get();
        $laporan   = [];

        foreach ($salesData as $sales) {
            // 🔹 TOTAL PENJUALAN per sales (header SO)
            $totalPenjualan = SalesOrder::where('sales_id', $sales->id)
                ->whereMonth('tanggal', $bulan)
                ->whereYear('tanggal', $tahun)
                ->sum('total_harga');

            // 🔹 TOTAL PEMBAYARAN (status diterima)
            $totalPembayaran = Pembayaran::whereHas('salesOrder', function ($q) use ($sales) {
                    $q->where('sales_id', $sales->id);
                })
                ->whereMonth('tanggal_pembayaran', $bulan)
                ->whereYear('tanggal_pembayaran', $tahun)
                ->where('status', 'diterima')
                ->sum('jumlah');

            $laporan[] = [
                'nama'              => $sales->user->name ?? '-',
                'kunjungan'         => KunjunganSales::where('sales_id', $sales->id)
                                            ->where('status', 'Berhasil')
                                            ->whereMonth('tanggal_kunjungan', $bulan)
                                            ->whereYear('tanggal_kunjungan', $tahun)
                                            ->count(),
                'penawaran'         => Penawaran::where('sales_id', $sales->id)
                                            ->where('status', 'diterima')
                                            ->whereMonth('tanggal_penawaran', $bulan)
                                            ->whereYear('tanggal_penawaran', $tahun)
                                            ->count(),
                'so'                => SalesOrder::where('sales_id', $sales->id)
                                            ->whereMonth('tanggal', $bulan)
                                            ->whereYear('tanggal', $tahun)
                                            ->count(),
                'total_penjualan'   => $totalPenjualan,
                'total_pembayaran'  => $totalPembayaran,
                'piutang'           => max($totalPenjualan - $totalPembayaran, 0),
            ];
        }

        $pdf = Pdf::loadView('laporan.pdf', [
            'laporan' => $laporan,
            'bulan'   => $bulan,
            'tahun'   => $tahun
        ]);

        return $pdf->stream('laporan_penjualan_' . $bulan . '_' . $tahun . '.pdf');
    }
}
