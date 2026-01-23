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

        // ===============================
        // COOLDOWN PROMOSI
        // ===============================
        $promotionCooldownMonths = 6;

        if (!empty($sales->last_promoted_at)) {
            $last = Carbon::parse($sales->last_promoted_at);
            if ($last->greaterThan($today->copy()->subMonths($promotionCooldownMonths))) {
                return [
                    'eligible' => false,
                    'matched_window' => null,
                    'monthly' => [],
                    'reason' => 'Baru dipromosikan — menunggu masa tunggu promosi',
                ];
            }
        }

        // ===============================
        // AMBIL 3 BULAN TERAKHIR (ROLLING)
        // ===============================
        $months = [];
        for ($i = 2; $i >= 0; $i--) {
            $months[] = $today->copy()->subMonths($i)->format('Y-m');
        }

        // ===============================
        // SIAPKAN DATA BULANAN
        // ===============================
        $monthly = [];
        foreach ($months as $m) {
            $monthly[$m] = [
                'sales' => 0.0,
                'target' => null,
            ];
        }

        // ===============================
        // TOTAL PENJUALAN PER BULAN
        // ===============================
        $rows = DB::table('sales_orders')
            ->select(
                DB::raw("TO_CHAR(tanggal, 'YYYY-MM') as ym"),
                DB::raw("SUM(COALESCE(total_harga,0)) as total")
            )
            ->where('sales_id', $sales->id)
            ->whereIn(DB::raw("TO_CHAR(tanggal, 'YYYY-MM')"), $months)
            ->groupBy('ym')
            ->get();

        foreach ($rows as $r) {
            if (isset($monthly[$r->ym])) {
                $monthly[$r->ym]['sales'] = (float) $r->total;
            }
        }

        // ===============================
        // TARGET PER BULAN
        // ===============================
        $targets = DB::table('target_sales')
            ->where('sales_id', $sales->id)
            ->whereIn(DB::raw("(tahun || '-' || LPAD(bulan::text, 2, '0'))"), $months)
            ->get();

        foreach ($targets as $t) {
            $key = $t->tahun . '-' . str_pad($t->bulan, 2, '0', STR_PAD_LEFT);
            if (isset($monthly[$key])) {
                $monthly[$key]['target'] = (float) $t->target;
            }
        }

        // ===============================
        // EVALUASI 3 BULAN TERAKHIR
        // ===============================
        $values = array_values($monthly);

        $a  = $values[0]['sales'];
        $b  = $values[1]['sales'];
        $c  = $values[2]['sales'];

        $ta = $values[0]['target'];
        $tb = $values[1]['target'];
        $tc = $values[2]['target'];

        $eligible =
            $ta > 0 && $tb > 0 && $tc > 0 &&
            $a > 0 && $b > 0 && $c > 0 &&
            $a < $b && $b < $c &&
            $a >= $ta && $b >= $tb && $c >= $tc;

        return [
            'eligible' => $eligible,
            'matched_window' => $eligible ? [$months[0], $months[2]] : null,
            'monthly' => $monthly,
            'reason' => $eligible
                ? 'Penjualan 3 bulan terakhir naik konsisten dan target terpenuhi'
                : null,
        ];
    }

}
