<?php

namespace App\Http\Controllers;

use App\TargetSales; // model existing
use App\Sales;
use App\LevelTarget; // model simple untuk level_targets
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class TargetSalesController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (auth()->user()->jabatan !== 'manajer') {
                abort(403, 'Hanya manajer yang dapat mengakses.');
            }
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $year = $request->filled('tahun') ? (int)$request->tahun : date('Y');
        $month = $request->filled('bulan') ? (int)$request->bulan : date('n');

        // ambil daftar sales (paginated)
        $salesQuery = Sales::with('wilayah')->orderBy('nama_sales');
        $sales = $salesQuery->paginate(5)->withQueryString();

        // ambil target_sales yang spesifik untuk sales visible pada bulan/tahun terpilih
        $salesIds = $sales->pluck('id')->toArray();
        $targetsQuery = TargetSales::whereIn('sales_id', $salesIds)
            ->where('tahun', $year)
            ->where('bulan', $month)
            ->get()
            ->keyBy('sales_id');

        // pastikan Collection (jaga-jaga jika suatu saat diubah)
        $targets = is_array($targetsQuery) ? collect($targetsQuery) : $targetsQuery;

        // ambil level defaults (cache per level) sebagai Collection
        $levelsQuery = LevelTarget::all()->keyBy('level');
        $levels = is_array($levelsQuery) ? collect($levelsQuery) : $levelsQuery;

        return view('target_sales.index', compact('sales','targets','levels','year','month'));
    }

    public function create()
    {
        $levels = LevelTarget::orderByRaw("
            CASE level
                WHEN 'Trainee' THEN 1
                WHEN 'Junior'  THEN 2
                WHEN 'Senior'  THEN 3
                ELSE 99
            END
        ")->get();

        return view('target_sales.create', compact('levels'));
    }


public function store(Request $request)
{
    // validasi tahun & bulan
    $request->validate([
        'tahun' => 'required|integer|min:2020',
        'bulan' => 'required|integer|min:1|max:12',
        'default_target' => 'required|array',
        'default_target.*' => 'required|numeric|min:0'
    ]);

    $tahun = (int)$request->tahun;
    $bulan = (int)$request->bulan;

    // semua default target yang diinput
    $defaults = $request->default_target;

    $salesList = Sales::all();
    $created = 0;

    DB::transaction(function() use ($tahun,$bulan,$salesList,$defaults,&$created) {
        foreach ($salesList as $s) {

            // tentukan target berdasarkan level sales
            $lvl = $s->level;
            $amount = isset($defaults[$lvl]) ? (float)$defaults[$lvl] : 0;

            // jika sudah ada data → skip
            $exists = TargetSales::where([
                'sales_id' => $s->id,
                'tahun' => $tahun,
                'bulan' => $bulan
            ])->exists();

            if ($exists) continue;

            TargetSales::create([
                'sales_id' => $s->id,
                'tahun' => $tahun,
                'bulan' => $bulan,
                'target' => $amount,
                'source' => 'default',
                'level_when_set' => $lvl,
                'status' => 'Aktif'
            ]);

            $now = now();

            if ($tahun === (int)$now->year && $bulan === (int)$now->month) {
                Sales::where('id', $s->id)->update([
                    'target_penjualan' => $amount
                ]);
            }


            $created++;
        }
    });

    return redirect()
        ->route('target_sales.index')
        ->with('success', "Target untuk bulan $bulan - $tahun berhasil dibuat.");
}


   public function update(Request $request, $salesId)
{
    $request->validate([
        'tahun' => 'required|integer|min:2020',
        'bulan' => 'required|integer|min:1|max:12',
        'amount' => 'required|numeric|min:0',
        'level'  => 'required|exists:level_targets,level'
    ]);

    DB::transaction(function () use ($request, $salesId) {

        Sales::where('id', $salesId)->update([
            'level' => $request->level
        ]);

        TargetSales::updateOrCreate(
            [
                'sales_id' => $salesId,
                'tahun' => $request->tahun,
                'bulan' => $request->bulan
            ],
            [
                'target' => $request->amount,
                'source' => 'override',
                'level_when_set' => $request->level,
                'overridden_by' => auth()->id(),
                'overridden_at' => now(),
                'status' => 'Aktif'
            ]
        );
        $now = now();

        if ((int)$request->tahun === (int)$now->year &&
            (int)$request->bulan === (int)$now->month) {

            Sales::where('id', $salesId)->update([
                'target_penjualan' => $request->amount
            ]);
        }

    
    });

    return back()->with('success','Target & level berhasil disimpan.');
}




    public function reset(Request $request, $salesId)
{
    $request->validate([
        'tahun' => 'required|integer|min:2020',
        'bulan' => 'required|integer|min:1|max:12',
    ]);

    TargetSales::where('sales_id', $salesId)
        ->where('tahun', $request->tahun)
        ->where('bulan', $request->bulan)
        ->where('source', 'override')
        ->update([
            'source' => 'default',
            'overridden_by' => null,
            'overridden_at' => null
        ]);

        $now = now();

        if ((int)$request->tahun === (int)$now->year &&
            (int)$request->bulan === (int)$now->month) {

            $defaultTarget = TargetSales::where('sales_id', $salesId)
                ->where('tahun', $request->tahun)
                ->where('bulan', $request->bulan)
                ->value('target');

            Sales::where('id', $salesId)->update([
                'target_penjualan' => $defaultTarget ?? 0
            ]);
        }

    return back()->with('success', 'Target dikembalikan ke default.');
}


    public function batal($tahun)
    {
        TargetSales::where('tahun', $tahun)->update(['status' => 'Nonaktif']);
        \App\Sales::query()->update(['target_penjualan' => 0]);

        return back()->with('success', 'Target berhasil Dibatalkan.');
    }

    public function applyDefaultsForPromotedSales(Sales $sales, $months = 12)
    {
        $level = $sales->level ?? null;
        if (! $level) {
            return;
        }

        $today = Carbon::now();

        $lt = LevelTarget::where('level', $level)
            ->orderByDesc('valid_from')
            ->first();

        if (! $lt) return;

        DB::transaction(function() use ($sales, $months, $today, $lt, $level) {
            for ($i = 0; $i < $months; $i++) {
                $dt = $today->copy()->addMonths($i);
                $y = (int) $dt->format('Y');
                $m = (int) $dt->format('n');

                $exists = TargetSales::where('sales_id', $sales->id)
                    ->where('tahun', $y)
                    ->where('bulan', $m)
                    ->exists();

                if ($exists) continue;

                TargetSales::create([
                    'sales_id' => $sales->id,
                    'tahun' => $y,
                    'bulan' => $m,
                    'target' => (float) $lt->amount,
                    'source' => 'default',
                    'level_when_set' => $level,
                    'status' => 'Aktif',
                ]);

                if ($y === now()->year && $m === now()->month) {
                Sales::where('id', $sales->id)->update([
                    'target_penjualan' => (float) $lt->amount
                ]);
            }

            }
        });
    }
}
