<?php

namespace App\Http\Controllers;

use App\LevelTarget;
use App\TargetSales;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use App\Sales;

class SalesManagerController extends Controller
{
    // Urutan level — ubah sesuai kebutuhan perusahaan Anda
    protected $levels = ['Trainee', 'Junior', 'Senior'];

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Tampilkan daftar sales + indikator performa (eligible promosi).
     */
    public function index(Request $request)
    {
        $query = Sales::with('wilayah');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function($q) use ($s) {
                $q->where('nama_sales', 'like', "%{$s}%")
                  ->orWhere('kode_sales', 'like', "%{$s}%");
            });
        }

        $paginator = $query
        ->orderByRaw("
            COALESCE(
                NULLIF(REGEXP_REPLACE(nama_sales, '[^0-9]', '', 'g'), '')::INTEGER,
                0
            ) ASC
        ")
        ->orderBy('nama_sales', 'ASC')
        ->paginate(5)
        ->withQueryString();



        // transformasi: untuk tiap sales hitung eligible berdasarkan 12 bulan terakhir
        $collection = $paginator->getCollection()->map(function($s) {
            $perf = $this->computePerformanceForSales($s);
            return (object)[
                'model' => $s,
                'eligible' => $perf['eligible'],
                'details' => $perf,
            ];
        });

        $paginator->setCollection($collection);

        return view('master.sales.manager.index', [
            'salesList' => $paginator,
            'levels' => $this->levels,
        ]);
    }

    /**
     * Promote: naikkan level sales ke next level.
     */
    public function promote(Request $request, $id)
    {
        $s = Sales::findOrFail($id);
        $current = $s->level ?? null;
        $next = $this->getNextLevel($current);

        if (! $next) {
            return back()->with('error', 'Tidak ada level berikutnya untuk promosi.');
        }

        // ambil default amount untuk level baru
        $lt = LevelTarget::where('level', $next)->orderByDesc('valid_from')->first();
        $levelAmount = $lt ? (float)$lt->amount : null;

        DB::transaction(function() use ($s, $next, $levelAmount) {
            // update level + timestamp promosi
            $s->level = $next;
            $s->last_promoted_at = Carbon::now();
            if (!is_null($levelAmount)) $s->target_penjualan = $levelAmount; // optional quick field
            $s->save();

            if (is_null($levelAmount)) {
                // tidak ada default untuk level baru -> jangan ubah target
                return;
            }

            // Hitungan: mulai dari bulan berikutnya (tidak menyentuh bulan ini atau bulan sebelumnya)
            $start = Carbon::now()->addMonth()->startOfMonth();
            $monthsToCreate = 12; // jumlah bulan ke depan yang otomatis diisi (ubah jika perlu)

            for ($i = 0; $i < $monthsToCreate; $i++) {
                $dt = $start->copy()->addMonths($i);
                $y = (int)$dt->format('Y');
                $m = (int)$dt->format('n');

                // cek ada baris override (manual) untuk bulan tersebut -> jika ada, skip
                $existing = TargetSales::where('sales_id', $s->id)
                    ->where('tahun', $y)
                    ->where('bulan', $m)
                    ->first();

                if ($existing) {
                    // jika ada dan source='default' -> update ke levelAmount
                    if (($existing->source ?? 'default') === 'default') {
                        $existing->update([
                            'target' => $levelAmount,
                            'level_when_set' => $next,
                            'status' => 'Aktif',
                        ]);
                    }
                    // jika source='override' (manual) -> jangan timpa
                    continue;
                }

                // jika belum ada baris sama sekali -> buat default baru
                TargetSales::create([
                    'sales_id' => $s->id,
                    'tahun' => $y,
                    'bulan' => $m,
                    'target' => $levelAmount,
                    'source' => 'default',
                    'level_when_set' => $next,
                    'status' => 'Aktif',
                ]);
            }
        });

        return back()->with('success', "Sales {$s->nama_sales} berhasil dipromosikan menjadi {$next}. Default target bulan berikutnya sudah di-apply (override manual tidak ditimpa).");
    }

    protected function getNextLevel($current)
    {
        if (empty($current)) {
            return $this->levels[0] ?? null;
        }
        $idx = array_search($current, $this->levels);
        if ($idx === false) return $this->levels[0] ?? null;
        return $this->levels[$idx + 1] ?? null;
    }

    /**
     * Hitung performa untuk satu sales.
     */
    protected function computePerformanceForSales(Sales $sales)
    {
        $today = Carbon::now();

        // konfigurasi cooldown (bulan)
        $promotionCooldownMonths = 6; // ubah sesuai kebijakan

        // jika sales baru saja dipromosikan dalam cooldown period -> langsung non-eligible
        if (!empty($sales->last_promoted_at)) {
            $last = Carbon::parse($sales->last_promoted_at);
            $cutoff = Carbon::now()->subMonths($promotionCooldownMonths);
            if ($last->greaterThan($cutoff)) {
                return [
                    'eligible' => false,
                    'matched_window' => null,
                    'windows_checked' => 0,
                    'monthly' => [], // atau Anda bisa mengembalikan monthly lengkap jika diinginkan
                    'visit_source' => Schema::hasTable('penawaran') ? 'penawaran' : (Schema::hasTable('kunjungan_sales') ? 'kunjungan_sales' : null),
                    'trend_type' => null,
                    'reason' => 'Baru dipromosikan — menunggu masa tunggu promosi',
                    'window_size' => 3,
                ];
            }
        }

        // 12 bulan terakhir, format YYYY-MM
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $dt = $today->copy()->subMonths($i);
            $months[] = $dt->format('Y-m');
        }

        // prepare monthly container
        $monthly = [];
        foreach ($months as $m) {
            $monthly[$m] = [
                'tahun' => substr($m,0,4),
                'bulan' => substr($m,5,2),
                'sales' => 0.0,
                'penawaran' => 0,
                'target' => null,
                'target_visits' => null, // akan diisi bila ada kolom di target_sales
            ];
        }

        // Ambil total penjualan per bulan menggunakan sales_order_details (sama seperti chart)
        if (Schema::hasTable('sales_orders') && Schema::hasTable('sales_order_details')) {
            $rows = DB::table('sales_order_details as sod')
                ->join('sales_orders as so', 'sod.sales_order_id', '=', 'so.id')
                ->select(DB::raw("DATE_FORMAT(so.tanggal, '%Y-%m') as ym"), DB::raw("SUM(sod.qty * sod.harga_satuan) as total"))
                ->where('so.sales_id', $sales->id)
                ->whereIn(DB::raw("DATE_FORMAT(so.tanggal, '%Y-%m')"), $months)
                ->groupBy('ym')
                ->get();

            foreach ($rows as $r) {
                if (isset($monthly[$r->ym])) $monthly[$r->ym]['sales'] = (float)$r->total;
            }
        } else {
            // fallback: hitung dari header jika ada field total_harga
            $rows = DB::table('sales_orders')
                ->select(DB::raw("DATE_FORMAT(tanggal, '%Y-%m') as ym"), DB::raw("SUM(IFNULL(total_harga,0)) as total"))
                ->where('sales_id', $sales->id)
                ->whereIn(DB::raw("DATE_FORMAT(tanggal, '%Y-%m')"), $months)
                ->groupBy('ym')
                ->get();
            foreach ($rows as $r) {
                if (isset($monthly[$r->ym])) $monthly[$r->ym]['sales'] = (float)$r->total;
            }
        }

        // Ambil penawaran per bulan (jika ada)
        if (Schema::hasTable('penawaran')) {
            $pen = DB::table('penawaran')
                ->select(DB::raw("DATE_FORMAT(tanggal_penawaran, '%Y-%m') as ym"), DB::raw("COUNT(*) as c"))
                ->where('sales_id', $sales->id)
                ->whereIn(DB::raw("DATE_FORMAT(tanggal_penawaran, '%Y-%m')"), $months)
                ->groupBy('ym')
                ->get();
            foreach ($pen as $r) if (isset($monthly[$r->ym])) $monthly[$r->ym]['penawaran'] = (int)$r->c;
        }

        // ambil target bila ada (jangan paksa target menjadi syarat di sini — tapi nanti kita butuhkan untuk validasi)
        $targetRows = DB::table('target_sales')
            ->where('sales_id', $sales->id)
            ->whereIn(DB::raw("CONCAT(tahun,'-',LPAD(bulan,2,'0'))"), $months)
            ->get();

        // cek apakah target_sales punya kolom untuk target penawaran/kunjungan
        $hasTargetVisitCol = Schema::hasColumn('target_sales', 'target_visit') || Schema::hasColumn('target_sales', 'target_penawaran') || Schema::hasColumn('target_sales', 'target_visits');

        foreach ($targetRows as $tr) {
            $k = $tr->tahun . '-' . str_pad($tr->bulan, 2, '0', STR_PAD_LEFT);
            if (! isset($monthly[$k])) continue;

            $monthly[$k]['target'] = is_null($tr->target) ? null : (float)$tr->target;

            if ($hasTargetVisitCol) {
                // normalisasi nama kolom target visit jika ada beberapa possible names
                if (isset($tr->target_visit)) {
                    $monthly[$k]['target_visits'] = is_null($tr->target_visit) ? null : (int)$tr->target_visit;
                } elseif (isset($tr->target_penawaran)) {
                    $monthly[$k]['target_visits'] = is_null($tr->target_penawaran) ? null : (int)$tr->target_penawaran;
                } elseif (isset($tr->target_visits)) {
                    $monthly[$k]['target_visits'] = is_null($tr->target_visits) ? null : (int)$tr->target_visits;
                } else {
                    $monthly[$k]['target_visits'] = null;
                }
            }
        }

        // Sliding window 3 bulan: cek strictly increasing penjualan dan setiap bulan memenuhi target (target harus >0)
        $keys = array_keys($monthly);
        $windowSize = 3;

        // inisialisasi flag/variabel supaya tidak ada undefined variable
        $eligibleBySales = false;
        $matchedWindowSales = null;

        $eligibleByPenawaran = false;
        $matchedWindowPenawaran = null;

        $windowsChecked = 0;

        // only check windows if we have enough months
        if (count($keys) >= $windowSize) {
            // cek sales (require target available & sales >= target setiap bulan)
            for ($i = 0; $i <= count($keys) - $windowSize; $i++) {
                $windowsChecked++;
                $w = array_slice($keys, $i, $windowSize);

                // safe access values
                $a = isset($monthly[$w[0]]['sales']) ? (float)$monthly[$w[0]]['sales'] : 0.0;
                $b = isset($monthly[$w[1]]['sales']) ? (float)$monthly[$w[1]]['sales'] : 0.0;
                $c = isset($monthly[$w[2]]['sales']) ? (float)$monthly[$w[2]]['sales'] : 0.0;

                $ta = isset($monthly[$w[0]]['target']) ? $monthly[$w[0]]['target'] : null;
                $tb = isset($monthly[$w[1]]['target']) ? $monthly[$w[1]]['target'] : null;
                $tc = isset($monthly[$w[2]]['target']) ? $monthly[$w[2]]['target'] : null;

                // jika target tersedia untuk semua 3 bulan -> periksa kondisi ketat
                if ($ta !== null && $tb !== null && $tc !== null) {
                    $taF = (float)$ta;
                    $tbF = (float)$tb;
                    $tcF = (float)$tc;

                    if (
                        $taF > 0 && $tbF > 0 && $tcF > 0
                        && $a > 0.0 && $b > 0.0 && $c > 0.0
                        && $a < $b && $b < $c
                        && $a >= $taF && $b >= $tbF && $c >= $tcF
                    ) {
                        $eligibleBySales = true;
                        $matchedWindowSales = [$w[0], $w[2]];
                        break;
                    }
                }
                // jika target belum lengkap -> skip window ini untuk pengecekan sales
            }

            // Penawaran: hanya diperhitungkan jika tabel target_sales punya kolom target_visits
            if ($hasTargetVisitCol && !$eligibleBySales) {
                for ($i = 0; $i <= count($keys) - $windowSize; $i++) {
                    $w = array_slice($keys, $i, $windowSize);

                    $pa = isset($monthly[$w[0]]['penawaran']) ? (int)$monthly[$w[0]]['penawaran'] : 0;
                    $pb = isset($monthly[$w[1]]['penawaran']) ? (int)$monthly[$w[1]]['penawaran'] : 0;
                    $pc = isset($monthly[$w[2]]['penawaran']) ? (int)$monthly[$w[2]]['penawaran'] : 0;

                    $pta = $monthly[$w[0]]['target_visits'] ?? null;
                    $ptb = $monthly[$w[1]]['target_visits'] ?? null;
                    $ptc = $monthly[$w[2]]['target_visits'] ?? null;

                    // hanya pertimbangkan window ini jika semua target_visits tersedia (>0)
                    if ($pta !== null && $ptb !== null && $ptc !== null) {
                        $ptaI = (int)$pta; $ptbI = (int)$ptb; $ptcI = (int)$ptc;
                        if (
                            $ptaI > 0 && $ptbI > 0 && $ptcI > 0
                            && $pa >= $ptaI && $pb >= $ptbI && $pc >= $ptcI
                            && $pa < $pb && $pb < $pc
                        ) {
                            $eligibleByPenawaran = true;
                            $matchedWindowPenawaran = [$w[0], $w[2]];
                            break;
                        }
                    }
                    // jika target_visits tidak lengkap -> skip window ini
                }
            }
        }

        // rule: eligible jika sales naik konsisten (dengan syarat target terpenuhi) OR penawaran naik konsisten (jika tersedia target_visits)
        $eligible = $eligibleBySales || $eligibleByPenawaran;
        $reason = null;
        if ($eligibleBySales && $eligibleByPenawaran) $reason = 'Penjualan & Penawaran naik konsisten (target terpenuhi)';
        elseif ($eligibleBySales) $reason = 'Penjualan naik konsisten (target terpenuhi)';
        elseif ($eligibleByPenawaran) $reason = 'Penawaran naik konsisten';

        return [
            'eligible' => (bool)$eligible,
            'matched_window' => $eligibleBySales ? $matchedWindowSales : $matchedWindowPenawaran,
            'windows_checked' => $windowsChecked,
            'monthly' => $monthly,
            'visit_source' => Schema::hasTable('penawaran') ? 'penawaran' : (Schema::hasTable('kunjungan_sales') ? 'kunjungan_sales' : null),
            'trend_type' => $eligibleBySales ? 'sales' : ($eligibleByPenawaran ? 'visits' : null),
            'reason' => $reason,
            'window_size' => $windowSize,
        ];
    }
}
