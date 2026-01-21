<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Sales;
use App\KunjunganSales;
use App\Penawaran;
use App\SalesOrder;
use App\Pembayaran;
use App\TargetSales;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
{
    $bulan = str_pad($request->input('bulan', date('m')), 2, '0', STR_PAD_LEFT);
    $tahun = $request->input('tahun', date('Y'));

    // -----------------------------
    // Data per Sales
    // -----------------------------
    $salesData = Sales::with('user')->get();
    $data = [];

    foreach ($salesData as $sales) {
        $target = TargetSales::where('sales_id', $sales->id)
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->where('status', 'Aktif')
            ->value('target') ?? 0;

        // 🔹 TOTAL PENJUALAN: pakai header sales_orders.total_harga
        $totalPenjualan = SalesOrder::where('sales_id', $sales->id)
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->sum('total_harga') ?? 0;

        $pencapaian = $target > 0 ? round(($totalPenjualan / $target) * 100, 2) : 0;

        $data[] = [
            'nama'       => $sales->user->name ?? '-',
            'target'     => $target,
            'penjualan'  => $totalPenjualan,
            'pencapaian' => $pencapaian
        ];
    }

    
    // -----------------------------
    // Ringkasan
    // -----------------------------
    $ringkasan = [
        'kunjungan' => KunjunganSales::where('status', 'Berhasil')
            ->whereMonth('tanggal_kunjungan', $bulan)
            ->whereYear('tanggal_kunjungan', $tahun)
            ->count(),
        'penawaran' => Penawaran::where('status', 'diterima')
            ->whereMonth('tanggal_penawaran', $bulan)
            ->whereYear('tanggal_penawaran', $tahun)
            ->count(),
        'so' => SalesOrder::whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->count(),

        // 🔹 Pembayaran: hanya yang status diterima
        'pembayaran' => Pembayaran::whereMonth('tanggal_pembayaran', $bulan)
            ->whereYear('tanggal_pembayaran', $tahun)
            ->where('status', 'diterima')
            ->sum('jumlah'),

        // 🔹 Total penjualan: SUM(total_harga)
        'penjualan' => SalesOrder::whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->sum('total_harga') ?? 0,
    ];

    // -----------------------------
    // Grafik Bulanan
    // -----------------------------
    $grafik = [
        'kunjungan'  => [],
        'penawaran'  => [],
        'so'         => [],
        'pembayaran' => [],
        'target'     => [],
        'penjualan'  => [],
        'persentase' => []
    ];

    for ($i = 1; $i <= 12; $i++) {
        $bulanLoop = str_pad($i, 2, '0', STR_PAD_LEFT);

        $grafik['kunjungan'][] = KunjunganSales::whereMonth('tanggal_kunjungan', $bulanLoop)
            ->whereYear('tanggal_kunjungan', $tahun)
            ->where('status', 'Berhasil')
            ->count();

        $grafik['penawaran'][] = Penawaran::whereMonth('tanggal_penawaran', $bulanLoop)
            ->whereYear('tanggal_penawaran', $tahun)
            ->where('status', 'diterima')
            ->count();

        $grafik['so'][] = SalesOrder::whereMonth('tanggal', $bulanLoop)
            ->whereYear('tanggal', $tahun)
            ->count();

        // pembayaran (hanya diterima)
        $grafik['pembayaran'][] = Pembayaran::whereMonth('tanggal_pembayaran', $bulanLoop)
            ->whereYear('tanggal_pembayaran', $tahun)
            ->where('status', 'diterima')
            ->sum('jumlah');

        // target & penjualan
        $totalTarget = TargetSales::where('tahun', $tahun)
            ->where('bulan', $bulanLoop)
            ->where('status', 'Aktif')
            ->sum('target');

        $totalPenjualan = SalesOrder::whereMonth('tanggal', $bulanLoop)
            ->whereYear('tanggal', $tahun)
            ->sum('total_harga') ?? 0;

        $grafik['target'][]     = $totalTarget;
        $grafik['penjualan'][]  = $totalPenjualan;
        $grafik['persentase'][] = $totalTarget > 0
            ? round(($totalPenjualan / $totalTarget) * 100, 2)
            : 0;
    }

    // -----------------------------
    // Per Sales (kunjungan, penawaran, SO, pembayaran)
    // -----------------------------
    $kunjungan_per_sales = Sales::with('user')->get()->map(function ($sales) use ($bulan, $tahun) {
        return [
            'nama'   => $sales->user->name ?? '-',
            'jumlah' => KunjunganSales::where('sales_id', $sales->id)
                ->where('status', 'Berhasil')
                ->whereMonth('tanggal_kunjungan', $bulan)
                ->whereYear('tanggal_kunjungan', $tahun)
                ->count()
        ];
    });

    $penawaran_per_sales = Sales::with('user')->get()->map(function ($sales) use ($bulan, $tahun) {
        return [
            'nama'   => $sales->user->name ?? '-',
            'jumlah' => Penawaran::where('sales_id', $sales->id)
                ->where('status', 'diterima')
                ->whereMonth('tanggal_penawaran', $bulan)
                ->whereYear('tanggal_penawaran', $tahun)
                ->count()
        ];
    });

    $so_per_sales = Sales::with('user')->get()->map(function ($sales) use ($bulan, $tahun) {
        return [
            'nama'   => $sales->user->name ?? '-',
            'jumlah' => SalesOrder::where('sales_id', $sales->id)
                ->whereMonth('tanggal', $bulan)
                ->whereYear('tanggal', $tahun)
                ->count()
        ];
    });

    $pembayaran_per_sales = Sales::with('user')->get()->map(function ($sales) use ($bulan, $tahun) {
        return [
            'nama'   => $sales->user->name ?? '-',
            'jumlah' => Pembayaran::whereHas('salesOrder', function ($query) use ($sales, $bulan, $tahun) {
                    $query->where('sales_id', $sales->id)
                          ->whereMonth('tanggal_pembayaran', $bulan)
                          ->whereYear('tanggal_pembayaran', $tahun);
                })
                ->where('status', 'diterima')
                ->count()
        ];
    });

    // -----------------------------
    // Rekap Bulan Terpilih
    // -----------------------------
    $totalTargetBulan = TargetSales::where('tahun', $tahun)
        ->where('bulan', $bulan)
        ->where('status', 'Aktif')
        ->sum('target');

    $totalPenjualanBulan = SalesOrder::whereMonth('tanggal', $bulan)
        ->whereYear('tanggal', $tahun)
        ->sum('total_harga') ?? 0;

    $pencapaianBulan = $totalTargetBulan > 0
        ? round(($totalPenjualanBulan / $totalTargetBulan) * 100, 2)
        : 0;

    // -----------------------------
    // Sales BELUM punya wilayah
    // -----------------------------
    $salesPendingWilayah = Sales::with('user')
        ->whereNull('wilayah_id')
        ->get();

    return view('dashboard.admin', compact(
        'data', 'ringkasan', 'bulan', 'tahun', 'grafik',
        'kunjungan_per_sales', 'penawaran_per_sales', 'so_per_sales', 'pembayaran_per_sales',
        'totalTargetBulan', 'totalPenjualanBulan', 'pencapaianBulan','salesPendingWilayah'
    ));
}

}
