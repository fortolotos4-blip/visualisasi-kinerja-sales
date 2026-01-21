<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Sales;
use App\KunjunganSales;
use App\Penawaran;
use App\SalesOrder;
use App\Pembayaran;
use App\TargetSales;
use App\KontribusiParameter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardSalesController extends Controller
{
    public function index(Request $request)
{
    $bulan = $request->input('bulan', date('m'));
    $tahun = $request->input('tahun', date('Y'));

    // Ambil data sales sesuai user login
    $sales = Sales::where('user_id', Auth::id())->first();
    if (!$sales) {
        abort(403, 'Akun ini tidak terhubung dengan data sales.');
    }

        $isPendingSales = is_null($sales->wilayah_id);

    // === TOTAL PENJUALAN: pakai header sales_orders.total_harga ===
    $totalPenjualan = SalesOrder::where('sales_id', $sales->id)
        ->whereMonth('tanggal', $bulan)
        ->whereYear('tanggal', $tahun)
        ->sum('total_harga') ?? 0;

    // Target penjualan dari tabel target_sales
    $targetRecord = TargetSales::where('sales_id', $sales->id)
        ->where('bulan', $bulan)
        ->where('tahun', $tahun)
        ->where('status', 'Aktif')
        ->first();

    $target     = $targetRecord->target ?? 0;
    $pencapaian = $target > 0 ? round(($totalPenjualan / $target) * 100, 2) : 0;

    // ---------------- Ringkasan dasar ----------------
    $ringkasan = [
        'kunjungan' => KunjunganSales::where('sales_id', $sales->id)
            ->whereMonth('tanggal_kunjungan', $bulan)
            ->whereYear('tanggal_kunjungan', $tahun)
            ->where('status','Berhasil')
            ->count(),
        'penawaran' => Penawaran::where('sales_id', $sales->id)
            ->whereMonth('tanggal_penawaran', $bulan)
            ->whereYear('tanggal_penawaran', $tahun)
            ->where('status','diterima')
            ->count(),
        'so' => SalesOrder::where('sales_id', $sales->id)
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->count(),
        'total_so_nilai' => $totalPenjualan,

        // pembayaran: hanya yang diterima
        'pembayaran' => DB::table('pembayaran')
            ->join('sales_orders', 'pembayaran.sales_order_id', '=', 'sales_orders.id')
            ->where('sales_orders.sales_id', $sales->id)
            ->whereMonth('pembayaran.tanggal_pembayaran', $bulan)
            ->whereYear('pembayaran.tanggal_pembayaran', $tahun)
            ->where('pembayaran.status', 'diterima')
            ->sum('pembayaran.jumlah'),
    ];

    // Tambahan untuk chart aktivitas
    $ringkasan['kunjungan_sukses_penawaran'] = KunjunganSales::where('sales_id', $sales->id)
        ->where('status', 'Berhasil')
        ->where('tujuan', 'Penawaran')
        ->whereMonth('tanggal_kunjungan', $bulan)
        ->whereYear('tanggal_kunjungan', $tahun)
        ->count();

    $ringkasan['penawaran_diterima'] = Penawaran::where('sales_id', $sales->id)
        ->where('status', 'diterima')
        ->whereMonth('tanggal_penawaran', $bulan)
        ->whereYear('tanggal_penawaran', $tahun)
        ->count();

    // SO lunas: pakai total_harga & pembayaran diterima
    $ringkasan['so_lunas'] = DB::table('sales_orders')
    ->leftJoin('pembayaran', 'sales_orders.id', '=', 'pembayaran.sales_order_id')
    ->where('sales_orders.sales_id', $sales->id)
    ->whereMonth('sales_orders.tanggal', $bulan)
    ->whereYear('sales_orders.tanggal', $tahun)
    ->groupBy('sales_orders.id', 'sales_orders.total_harga')
    ->havingRaw("
        COALESCE(SUM(
            CASE 
                WHEN pembayaran.status = 'diterima' 
                THEN pembayaran.jumlah 
                ELSE 0 
            END
        ), 0) >= sales_orders.total_harga
    ")
    ->count();

    // ---------------- Kontribusi & skor ----------------
    $kontribusi = KontribusiParameter::where('periode_bulan', $bulan)
        ->where('periode_tahun', $tahun)
        ->where('status','Aktif')
        ->first();

    $bobot_penjualan   = $kontribusi->bobot_penjualan   ?? 0;
    $bobot_kunjungan   = $kontribusi->bobot_kunjungan   ?? 0;
    $bobot_penawaran   = $kontribusi->bobot_penawaran   ?? 0;
    $target_kunjungan  = $kontribusi->target_kunjungan  ?? 0;
    $target_penawaran  = $kontribusi->target_penawaran  ?? 0;

    // target_penjualan untuk hitung skor
    $target_penjualan = TargetSales::where('sales_id', $sales->id)
        ->where('bulan', $bulan)
        ->where('tahun', $tahun)
        ->where('status', 'Aktif')
        ->value('target') ?? 0;

    $pencapaian_penjualan = $target_penjualan > 0
        ? round(($totalPenjualan / $target_penjualan) * 100, 2)
        : 0;

    $skor_penjualan = $target_penjualan > 0
        ? round($pencapaian_penjualan * ($bobot_penjualan / 100), 2)
        : 0;

    // Realisasi kunjungan sukses
    $totalKunjungan = KunjunganSales::where('sales_id', $sales->id)
        ->whereMonth('tanggal_kunjungan', $bulan)
        ->whereYear('tanggal_kunjungan', $tahun)
        ->where('status', 'Berhasil')
        ->count();

    $pencapaian_kunjungan = $target_kunjungan > 0
        ? round(($totalKunjungan / $target_kunjungan) * 100, 2)
        : 0;

    $skor_kunjungan = $bobot_kunjungan > 0
        ? round($pencapaian_kunjungan * ($bobot_kunjungan / 100), 2)
        : 0;

    // Realisasi penawaran diterima
    $totalPenawaran = Penawaran::where('sales_id', $sales->id)
        ->whereMonth('tanggal_penawaran', $bulan)
        ->whereYear('tanggal_penawaran', $tahun)
        ->where('status', 'diterima')
        ->count();

    $pencapaian_penawaran = $target_penawaran > 0
        ? round(($totalPenawaran / $target_penawaran) * 100, 2)
        : 0;

    $skor_penawaran = $bobot_penawaran > 0
        ? round($pencapaian_penawaran * ($bobot_penawaran / 100), 2)
        : 0;

    // ---------------- Warning piutang ----------------
    $isWarning = false;
    $warningMessages = [];

    $today = now();
    $lastDayOfMonth = now()->endOfMonth();
    $daysRemaining = $today->diffInDays($lastDayOfMonth);

    if ((int)$bulan === (int)date('m') && (int)$tahun === (int)date('Y') && $daysRemaining <= 10) {
        if ($pencapaian < 80) {
            $isWarning = true;
            $warningMessages[] = "Pencapaian target penjualan masih di bawah 80%.";
        }

        $ratioKunjunganPenawaran = $ringkasan['kunjungan'] > 0
            ? ($ringkasan['penawaran'] / $ringkasan['kunjungan']) * 100
            : 0;

        if ($ratioKunjunganPenawaran < 10) {
            $isWarning = true;
            $warningMessages[] = "Rasio kunjungan ke penawaran masih di bawah 10%.";
        }

        $piutangBelumLunas = DB::table('sales_orders')
        ->leftJoin('pembayaran', 'sales_orders.id', '=', 'pembayaran.sales_order_id')
        ->where('sales_orders.sales_id', $sales->id)
        ->whereMonth('sales_orders.tanggal', $bulan)
        ->whereYear('sales_orders.tanggal', $tahun)
        ->groupBy('sales_orders.id', 'sales_orders.total_harga')
        ->havingRaw("
            COALESCE(SUM(
                CASE 
                    WHEN pembayaran.status = 'diterima' 
                    THEN pembayaran.jumlah 
                    ELSE 0 
                END
            ), 0) < sales_orders.total_harga
        ")
        ->count();

        if ($piutangBelumLunas > 0) {
            $isWarning = true;
            $warningMessages[] = "Masih ada pembayaran menunggak yang belum dilunasi.";
        }
    }

    // ---------------- Card penjualan (data) ----------------
    $data = [[
        'nama'            => $sales->user->name ?? '-',
        'target'          => $target,
        'penjualan'       => $totalPenjualan,
        'pencapaian'      => $pencapaian,
        'bobot_penjualan' => $bobot_penjualan,
        'skor_penjualan'  => $skor_penjualan,
    ]];

    // ---------------- Card kontribusi (data2) ----------------
    $data2 = [[
        'nama' => $sales->user->name ?? '-',

        // Penjualan
        'target_penjualan'        => $target_penjualan,
        'realisasi_penjualan'     => $totalPenjualan,
        'pencapaian_penjualan'    => $pencapaian_penjualan,

        // Kunjungan
        'bobot_kunjungan'         => $bobot_kunjungan,
        'target_kunjungan'        => $target_kunjungan,
        'realisasi_kunjungan'     => $totalKunjungan,
        'pencapaian_kunjungan'    => $pencapaian_kunjungan,
        'skor_kunjungan'          => $skor_kunjungan,

        // Penawaran
        'bobot_penawaran'         => $bobot_penawaran,
        'target_penawaran'        => $target_penawaran,
        'realisasi_penawaran'     => $totalPenawaran,
        'pencapaian_penawaran'    => $pencapaian_penawaran,
        'skor_penawaran'          => $skor_penawaran,
    ]];

    // ---------------- Data bulanan untuk chart ----------------
    $bulanLabels = [];
    $dataTargetBulanan = [];
    $dataRealisasiBulanan = [];
    $dataKunjunganBulanan = [];
    $dataPenawaranBulanan = [];
    $dataSOBulanan = [];

    for ($b = 1; $b <= 12; $b++) {
        $bulanLabels[] = date('M', mktime(0,0,0,$b,1));

        $targetBulan = TargetSales::where('sales_id', $sales->id)
            ->where('bulan', $b)
            ->where('tahun', $tahun)
            ->where('status','Aktif')
            ->value('target') ?? 0;
        $dataTargetBulanan[] = $targetBulan;

        // realisasi bulanan: sum(total_harga)
        $realisasiBulan = SalesOrder::where('sales_id', $sales->id)
            ->whereMonth('tanggal', $b)
            ->whereYear('tanggal', $tahun)
            ->sum('total_harga') ?? 0;
        $dataRealisasiBulanan[] = $realisasiBulan;

        $dataKunjunganBulanan[] = KunjunganSales::where('sales_id', $sales->id)
            ->whereMonth('tanggal_kunjungan', $b)
            ->whereYear('tanggal_kunjungan', $tahun)
            ->where('status', 'Berhasil')
            ->count();

        $dataPenawaranBulanan[] = Penawaran::where('sales_id', $sales->id)
            ->whereMonth('tanggal_penawaran', $b)
            ->whereYear('tanggal_penawaran', $tahun)
            ->where('status', 'diterima')
            ->count();

        $dataSOBulanan[] = SalesOrder::where('sales_id', $sales->id)
            ->whereMonth('tanggal', $b)
            ->whereYear('tanggal', $tahun)
            ->count();
    }

    $total_skor = $skor_penjualan + $skor_kunjungan + $skor_penawaran;

    return view('dashboard.sales', compact(
        'data','data2', 'ringkasan', 'bulan', 'tahun',
        'isWarning', 'warningMessages', 'kontribusi',
        'bulanLabels','dataTargetBulanan','dataRealisasiBulanan',
        'dataKunjunganBulanan','dataPenawaranBulanan','dataSOBulanan',
        'total_skor', 'isPendingSales'
    ));
}

}
