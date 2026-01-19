<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Sales;
use App\KunjunganSales;
use App\Pembayaran;
use App\Penawaran;
use App\SalesOrder;
use App\TargetSales;
use App\KontribusiParameter;
use Illuminate\Support\Facades\DB;

class DashboardManagerController extends Controller
{
    public function index(Request $request)
    {
        $bulan = (int) $request->input('bulan', date('m'));
        $tahun = (int) $request->input('tahun', date('Y'));

        // Ambil kontribusi parameter aktif (jika ada)
        $kontribusi = KontribusiParameter::where('periode_bulan', $bulan)
            ->where('periode_tahun', $tahun)
            ->where('status', 'Aktif')
            ->first();

        // Default nilai jika tidak ada kontribusi aktif
        $bobot_kunjungan  = $kontribusi->bobot_kunjungan ?? 0;
        $bobot_penawaran  = $kontribusi->bobot_penawaran ?? 0;
        $bobot_penjualan  = $kontribusi->bobot_penjualan ?? 0;
        $target_kunjungan = $kontribusi->target_kunjungan ?? 0;
        $target_penawaran = $kontribusi->target_penawaran ?? 0;

        $salesData = Sales::with('user')->get();
        $data = [];

        foreach ($salesData as $sales) {
            // Siapkan array monthly (1..12)
            $monthly = [];

            for ($m = 1; $m <= 12; $m++) {
                // === Target penjualan per sales per bulan ===
                $target_penjualan = TargetSales::where('sales_id', $sales->id)
                    ->where('bulan', $m)
                    ->where('tahun', $tahun)
                    ->value('target') ?? 0;

                // === Total penjualan (Rp) per bulan ===
                // utama: pakai total_harga (sudah termasuk diskon + PPN)
                $totalPenjualan = SalesOrder::where('sales_id', $sales->id)
                    ->whereMonth('tanggal', $m)
                    ->whereYear('tanggal', $tahun)
                    ->sum('total_harga');

                // fallback legacy (data lama belum punya total_harga)
                if ($totalPenjualan == 0) {
                    $soIds = SalesOrder::where('sales_id', $sales->id)
                        ->whereMonth('tanggal', $m)
                        ->whereYear('tanggal', $tahun)
                        ->pluck('id');

                    if ($soIds->count()) {
                        $totalPenjualan = DB::table('sales_order_details')
                            ->whereIn('sales_order_id', $soIds)
                            ->sum(DB::raw('qty * harga_satuan'));
                    }

                    if (!$totalPenjualan) {
                        $totalPenjualan = 0;
                    }
                }

                $pencapaian_penjualan = $target_penjualan > 0
                    ? round(($totalPenjualan / $target_penjualan) * 100, 2)
                    : 0;

                $skor_penjualan = round($pencapaian_penjualan * ($bobot_penjualan / 100), 2);

                // === Total kunjungan per bulan ===
                $totalKunjungan = KunjunganSales::where('sales_id', $sales->id)
                    ->whereMonth('tanggal_kunjungan', $m)
                    ->whereYear('tanggal_kunjungan', $tahun)
                    ->where('status', 'Berhasil')
                    ->count();

                $pencapaian_kunjungan = $target_kunjungan > 0
                    ? round(($totalKunjungan / $target_kunjungan) * 100, 2)
                    : 0;

                $skor_kunjungan = round($pencapaian_kunjungan * ($bobot_kunjungan / 100), 2);

                // === Total penawaran per bulan ===
                $totalPenawaran = Penawaran::where('sales_id', $sales->id)
                    ->whereMonth('tanggal_penawaran', $m)
                    ->whereYear('tanggal_penawaran', $tahun)
                    ->where('status', 'diterima')
                    ->count();

                $pencapaian_penawaran = $target_penawaran > 0
                    ? round(($totalPenawaran / $target_penawaran) * 100, 2)
                    : 0;

                $skor_penawaran = round($pencapaian_penawaran * ($bobot_penawaran / 100), 2);

                $total_skor = $skor_penjualan + $skor_kunjungan + $skor_penawaran;

                $monthly[$m] = [
                    'kunjungan'             => $totalKunjungan,
                    'penawaran'             => $totalPenawaran,
                    'target_penjualan'      => $target_penjualan,
                    'penjualan'             => $totalPenjualan,
                    'skor_penjualan'        => $skor_penjualan,
                    'skor_kunjungan'        => $skor_kunjungan,
                    'skor_penawaran'        => $skor_penawaran,
                    'total_skor'            => $total_skor,
                    'pencapaian_penjualan'  => $pencapaian_penjualan,
                    'pencapaian_kunjungan'  => $pencapaian_kunjungan,
                    'pencapaian_penawaran'  => $pencapaian_penawaran,
                ];
            }

            // data bulan yang dipilih (untuk tabel ringkas di atas)
            $bulanDipilih = $bulan;

            $targetPenjualanBulanDipilih = $monthly[$bulanDipilih]['target_penjualan'] ?? 0;
            $totalPenjualanBulanDipilih  = $monthly[$bulanDipilih]['penjualan'] ?? 0;
            $totalKunjunganBulanDipilih  = $monthly[$bulanDipilih]['kunjungan'] ?? 0;
            $totalPenawaranBulanDipilih  = $monthly[$bulanDipilih]['penawaran'] ?? 0;
            $totalSkorBulanDipilih       = $monthly[$bulanDipilih]['total_skor'] ?? 0;

            $data[] = [
                'sales_id'         => $sales->id,
                'nama'             => $sales->user->name ?? '-',
                'monthly'          => $monthly,
                // tabel ringkas bulan aktif:
                'kunjungan'        => $totalKunjunganBulanDipilih,
                'penawaran'        => $totalPenawaranBulanDipilih,
                'target_penjualan' => $targetPenjualanBulanDipilih,
                'penjualan'        => $totalPenjualanBulanDipilih,
                'skor_penjualan'   => $monthly[$bulanDipilih]['skor_penjualan'] ?? 0,
                'skor_kunjungan'   => $monthly[$bulanDipilih]['skor_kunjungan'] ?? 0,
                'skor_penawaran'   => $monthly[$bulanDipilih]['skor_penawaran'] ?? 0,
                'total_skor'       => $totalSkorBulanDipilih,
            ];
        }

        // urutkan berdasarkan angka di nama (misal "sales1", "sales2", ...)
        $dataCollection = collect($data)
            ->sortBy(function ($item) {
                return intval(filter_var($item['nama'], FILTER_SANITIZE_NUMBER_INT));
            })
            ->values();

        // MIN dan MAX untuk bulan yang dipilih
        $maxData = [
            'nama'      => $dataCollection->sortByDesc('penjualan')->first()['nama'] ?? '-',
            'kunjungan' => $dataCollection->max('kunjungan'),
            'penawaran' => $dataCollection->max('penawaran'),
            'penjualan' => $dataCollection->max('penjualan'),
        ];

        $minData = [
            'nama'      => $dataCollection->sortBy('penjualan')->first()['nama'] ?? '-',
            'kunjungan' => $dataCollection->min('kunjungan'),
            'penawaran' => $dataCollection->min('penawaran'),
            'penjualan' => $dataCollection->min('penjualan'),
        ];

        // === Grafik Target vs Realisasi Penjualan (agregat tahunan) ===
        $grafik = ['target' => [], 'penjualan' => []];

        for ($i = 1; $i <= 12; $i++) {
            $totalTarget = TargetSales::where('bulan', $i)
                ->where('tahun', $tahun)
                ->sum('target');

            $totalPenjualan = SalesOrder::whereMonth('tanggal', $i)
                ->whereYear('tanggal', $tahun)
                ->sum('total_harga');

            // fallback legacy
            if ($totalPenjualan == 0) {
                $soIds = SalesOrder::whereMonth('tanggal', $i)
                    ->whereYear('tanggal', $tahun)
                    ->pluck('id');

                if ($soIds->count()) {
                    $totalPenjualan = DB::table('sales_order_details')
                        ->whereIn('sales_order_id', $soIds)
                        ->sum(DB::raw('qty * harga_satuan'));
                }

                if (!$totalPenjualan) {
                    $totalPenjualan = 0;
                }
            }

            $grafik['target'][]    = $totalTarget;
            $grafik['penjualan'][] = $totalPenjualan;
        }

        // === Grafik Total Kunjungan & Penawaran (agregat tahunan) ===
        $grafikKunjungan = [];
        $grafikPenawaran = [];

        for ($b = 1; $b <= 12; $b++) {
            $grafikKunjungan[] = KunjunganSales::whereMonth('tanggal_kunjungan', $b)
                ->whereYear('tanggal_kunjungan', $tahun)
                ->where('status', 'Berhasil')
                ->count();

            $grafikPenawaran[] = Penawaran::whereMonth('tanggal_penawaran', $b)
                ->whereYear('tanggal_penawaran', $tahun)
                ->where('status', 'diterima')
                ->count();
        }

        return view('dashboard.manager', [
            'data'            => $dataCollection,
            'bulan'           => $bulan,
            'tahun'           => $tahun,
            'kontribusi'      => $kontribusi,
            'grafik'          => $grafik,
            'grafikKunjungan' => $grafikKunjungan,
            'grafikPenawaran' => $grafikPenawaran,
            'maxData'         => $maxData,
            'minData'         => $minData,
        ]);
    }
}
