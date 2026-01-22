<?php

namespace App\Http\Controllers;

use App\SalesOrder;
use App\Penawaran;
use App\PenawaranDetail;
use App\Produk;
use App\Customer;
use App\Sales;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;



class PenawaranController extends Controller
{
    // Generate Nomor Penawaran Unik dan Otomatis
    private function generateNomorPenawaran()
    {
        $prefix = 'PN-';
        $today = now()->format('Ymd'); // 20250728

        $last = Penawaran::where('nomor_penawaran', 'like', $prefix . $today . '%')
            ->orderBy('nomor_penawaran', 'desc')
            ->first();

        if ($last) {
            $lastNumber = (int)substr($last->nomor_penawaran, -3);
            $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '001';
        }

        return $prefix . $today . $newNumber;
    }

    /**
     * Batas kebijakan harga kesepakatan:
     * contoh: max diskon 10%, max kenaikan 10%.
     * Silakan ubah sesuai kebijakan perusahaan.
     */
    private float $maxDiscountPct = 0.10; // 10% di bawah harga pabrik
    private float $maxIncreasePct = 0.10; // 10% di atas harga pabrik

    /**
     * Hitung batas bawah & atas harga yang boleh dipakai sales
     * berdasarkan harga pabrik.
     */
    private function getAllowedPriceRange(float $hargaPabrik): array
    {
        $min = round($hargaPabrik * (1 - $this->maxDiscountPct));
        $max = round($hargaPabrik * (1 + $this->maxIncreasePct));
        return [$min, $max];
    }

    // Tampilkan penawaran milik sales
    public function index(Request $request)
    {
        $user = Auth::user();

        // Jika sales
        if ($user->jabatan === 'sales') {
            $sales = Sales::where('user_id', $user->id)->first();

            if (!$sales) {
                return redirect()->back()->withErrors('Data sales tidak ditemukan.');
            }

            $customerIds = Penawaran::where('sales_id', $sales->id)
                        ->pluck('customer_id')
                        ->unique();

            $customers = Customer::whereIn('id', $customerIds)->get();

            // Query penawaran milik sales ini (eager load details & customer)
            $query = Penawaran::with(['customer','details'])
                   ->where('sales_id', $sales->id);

            // Filter berdasarkan tanggal jika ada
            if ($request->filled('tanggal')) {
                $query->whereDate('tanggal_penawaran', $request->tanggal);
            }

            // Filter berdasarkan customer_id jika ada
            if ($request->filled('customer_id')) {
                $query->where('customer_id', $request->customer_id);
            }

            $penawaran = $query->orderBy('created_at', 'desc')->paginate(5);

            return view('penawaran.sales.index', compact('penawaran', 'customers'));
        }

        // Jika admin
        if ($user->jabatan === 'admin') {
            // eager load details, produk di details bisa di-join saat diperlukan
            $penawaran = Penawaran::with(['details', 'customer', 'sales'])
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            return view('penawaran.index', compact('penawaran'));
        }

        // Jika jabatan tidak dikenali
        abort(403, 'Unauthorized');
    }

    // Form input penawaran (sales)
    public function create()
    {
        $user = Auth::user();
        $sales = Sales::where('user_id', $user->id)->first();

        $produk = Produk::all();

        // 🔹 hanya ambil customer milik sales yang statusnya "baru"
        $customers = Customer::where('user_id', $user->id)
            ->where('status_customer', 'baru')
            ->orderBy('nama_customer')
            ->get();

        return view('penawaran.sales.create', compact('produk', 'customers'));
    }

    // Simpan penawaran (sales) — sekarang menerima items[]
    // Simpan penawaran (sales) — sekarang menerima items[] + diskon_global_pct
public function store(Request $request)
{
    $sales = Sales::where('user_id', Auth::id())->first();

    if (!$sales) {
        return back()->withErrors('Data sales tidak ditemukan.')->withInput();
    }

    $request->validate([
        'customer_id' => 'required|exists:customers,id',
        'tanggal_penawaran' => 'required|date',
        'items' => 'required|array|min:1',
        'items.*.product_id' => 'required|integer|exists:produk,id',
        'items.*.qty' => 'required|integer|min:1',
        'items.*.harga_kesepakatan' => 'required|numeric|min:0',
        'items.*.alasan' => 'nullable|string',
        'diskon_global_pct' => 'nullable|numeric|min:0|max:10', // ⬅️ batas 0–10%
    ]);

    DB::beginTransaction();
    try {
        $penawaran = Penawaran::create([
            'nomor_penawaran' => $this->generateNomorPenawaran(),
            'sales_id' => $sales->id,
            'customer_id' => $request->customer_id,
            'total_harga' => 0,           // akan di-update lagi
            'total_bruto' => 0,           // kolom baru
            'diskon_global_pct' => 0,
            'diskon_global_rp' => 0,
            'ppn_rp' => 0,
            'tanggal_penawaran' => $request->tanggal_penawaran,
            'status' => 'diajukan',
            'keterangan' => $request->keterangan,
        ]);

        $totalBruto = 0;

        foreach ($request->items as $it) {
            $produk = Produk::find($it['product_id']);
            if (!$produk) {
                DB::rollBack();
                return back()->withErrors('Produk tidak ditemukan.')->withInput();
            }

            $hargaPabrik = (float) $produk->harga;
            $hargaKesepakatan = (float) $it['harga_kesepakatan'];
            $qty = (int) $it['qty'];
            $subtotal = $qty * $hargaKesepakatan;

            // VALIDASI RANGE HARGA KESEPAKATAN
            [$minAllowed, $maxAllowed] = $this->getAllowedPriceRange($hargaPabrik);
            if ($hargaKesepakatan < $minAllowed || $hargaKesepakatan > $maxAllowed) {
                DB::rollBack();
                return back()
                    ->withErrors([
                        "Harga kesepakatan untuk produk {$produk->nama_produk} harus di antara Rp " .
                        number_format($minAllowed, 0, ',', '.') . " dan Rp " .
                        number_format($maxAllowed, 0, ',', '.') . "."
                    ])
                    ->withInput();
            }

            PenawaranDetail::create([
                'penawaran_id' => $penawaran->id,
                'product_id' => $produk->id,
                'product_name' => $produk->nama_produk,
                'qty' => $qty,
                'harga_pabrik' => $hargaPabrik,
                'harga_kesepakatan' => $hargaKesepakatan,
                'subtotal' => $subtotal,
                'satuan' => $produk->satuan,        // ⬅️ ambil dari produk
                'alasan' => $it['alasan'] ?? null,  // boleh tetap disimpan kalau masih mau pakai
            ]);

            $totalBruto += $subtotal;
        }

        // ==== Hitung diskon & PPN di server ====
        $diskonPct = (float) ($request->diskon_global_pct ?? 0);
        if ($diskonPct < 0) $diskonPct = 0;
        if ($diskonPct > 10) $diskonPct = 10; // hard limit

        $diskonRp = round($totalBruto * $diskonPct / 100);
        $dpp = $totalBruto - $diskonRp;              // Dasar Pengenaan Pajak
        $ppnRp = round($dpp * 0.11);                 // 11% PPN
        $grandTotal = $dpp + $ppnRp;

        $penawaran->update([
            'total_bruto' => $totalBruto,
            'diskon_global_pct' => $diskonPct,
            'diskon_global_rp' => $diskonRp,
            'ppn_rp' => $ppnRp,
            'total_harga' => $grandTotal, // ⬅️ grand total yang dipakai sistem
        ]);

        DB::commit();

        return redirect()->route('penawaran.sales.index')->with('success', 'Penawaran berhasil diajukan.');
    } /*catch (\Exception $e) {
    DB::rollBack();
    dd($e->getMessage(), $e->getTraceAsString());
}*/

    catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Error store penawaran: ' . $e->getMessage());
        return back()->withErrors('Terjadi kesalahan saat menyimpan penawaran.')->withInput();
    }
}


    // Edit penawaran (sales, jika masih diajukan)
    public function edit(Request $request, $id)
    {
        $penawaran = Penawaran::with('details')->findOrFail($id);
        $user = Auth::user();

        if ($user->jabatan === 'sales') {
            $sales = Sales::where('user_id', $user->id)->first();

            if (!$sales || $penawaran->sales_id !== $sales->id || $penawaran->status !== 'diajukan') {
                abort(403, 'Unauthorized');
            }

            $produk = Produk::all();
            $customers = Customer::all();

            // ambil nomor halaman dari request, default 1
            $page = $request->input('page', 1);

            return view('penawaran.sales.edit', compact('penawaran', 'produk', 'customers','page'));
        }

        if ($user->jabatan === 'admin') {

            // ambil nomor halaman dari request, default 1
            $page = $request->input('page', 1);
            
            return view('penawaran.edit', compact('penawaran','page'));
        }

        abort(403, 'Unauthorized');
    }

    // Generate Nomor SO Unik dan Otomatis
    private function generateNomorSO()
    {
        $prefix = 'SO-';
        $today = now()->format('Ymd');
        $last = SalesOrder::where('nomor_so', 'like', $prefix . $today . '%')->orderBy('nomor_so', 'desc')->first();
        $next = $last ? str_pad(((int)substr($last->nomor_so, -3)) + 1, 3, '0', STR_PAD_LEFT) : '001';
        return $prefix . $today . $next;
    }

    // Konvert Status Customer apabila ok
    public function convert($id)
    {
        $penawaran = Penawaran::findOrFail($id);

        // Ubah status jadi 'setuju'
        $penawaran->update([
            'status' => 'setuju'
        ]);

        $penawaran->save();
        
        return back()->with('success', 'Penawaran berhasil dikonfirmasi oleh customer.');
    }

    // apabila customer menolak 
    public function reject($id)
    {
        $penawaran = Penawaran::findOrFail($id);

        // Ubah status jadi 'batal'
        $penawaran->update([
            'status' => 'batal'
        ]);

        $penawaran->save();
        
        return back()->with('success', 'Penawaran berhasil dibatalkan.');
    }

    // Update penawaran (sales atau admin)
public function update(Request $request, $id)
{
    $penawaran = Penawaran::with('details')->findOrFail($id);
    $user = Auth::user();

    if ($user->jabatan === 'sales') {
        $sales = Sales::where('user_id', $user->id)->first();

        if (!$sales || $penawaran->sales_id !== $sales->id || $penawaran->status !== 'diajukan') {
            abort(403, 'Unauthorized');
        }

        // validate items array
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'tanggal_penawaran' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:produk,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.harga_kesepakatan' => 'required|numeric|min:0',
            'items.*.alasan' => 'nullable|string',
            'diskon_global_pct' => 'nullable|numeric|min:0|max:10',
        ]);

        DB::beginTransaction();
        try {
            // update header dasar
            $penawaran->update([
                'customer_id' => $request->customer_id,
                'tanggal_penawaran' => $request->tanggal_penawaran,
                'keterangan' => $request->keterangan,
            ]);

            // hapus detail lama
            PenawaranDetail::where('penawaran_id', $penawaran->id)->delete();

            $totalBruto = 0;

            foreach ($request->items as $it) {
                $produk = Produk::find($it['product_id']);
                if (!$produk) {
                    DB::rollBack();
                    return back()->withErrors('Produk tidak ditemukan.')->withInput();
                }

                $hargaPabrik = (float) $produk->harga;
                $hargaKesepakatan = (float) $it['harga_kesepakatan'];
                $qty = (int) $it['qty'];
                $subtotal = $qty * $hargaKesepakatan;

                // VALIDASI RANGE
                [$minAllowed, $maxAllowed] = $this->getAllowedPriceRange($hargaPabrik);
                if ($hargaKesepakatan < $minAllowed || $hargaKesepakatan > $maxAllowed) {
                    DB::rollBack();
                    return back()
                        ->withErrors([
                            "Harga kesepakatan untuk produk {$produk->nama_produk} harus di antara Rp " .
                            number_format($minAllowed, 0, ',', '.') . " dan Rp " .
                            number_format($maxAllowed, 0, ',', '.') . "."
                        ])
                        ->withInput();
                }

                PenawaranDetail::create([
                    'penawaran_id' => $penawaran->id,
                    'product_id' => $produk->id,
                    'product_name' => $produk->nama_produk,
                    'qty' => $qty,
                    'harga_pabrik' => $hargaPabrik,
                    'harga_kesepakatan' => $hargaKesepakatan,
                    'subtotal' => $subtotal,
                    'satuan' => $produk->satuan ?? '-',
                    'alasan' => $it['alasan'] ?? null,
                ]);

                $totalBruto += $subtotal;
            }

            // ==== Hitung diskon & PPN lagi ====
            $diskonPct = (float) ($request->diskon_global_pct ?? 0);
            if ($diskonPct < 0) $diskonPct = 0;
            if ($diskonPct > 10) $diskonPct = 10;

            $diskonRp = round($totalBruto * $diskonPct / 100);
            $dpp = $totalBruto - $diskonRp;
            $ppnRp = round($dpp * 0.11);
            $grandTotal = $dpp + $ppnRp;

            $penawaran->update([
                'total_bruto' => $totalBruto,
                'diskon_global_pct' => $diskonPct,
                'diskon_global_rp' => $diskonRp,
                'ppn_rp' => $ppnRp,
                'total_harga' => $grandTotal,
            ]);

            DB::commit();

            $page = $request->input('page', 1);

            return redirect()->route('penawaran.sales.index', ['page' => $page])
                             ->with('success', 'Penawaran berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error update penawaran: '.$e->getMessage());
            return back()->withErrors('Terjadi kesalahan saat memperbarui penawaran.')->withInput();
        }
    }
}


    // Hapus penawaran (sales)
    public function destroy(Request $request, $id)
    {
        $penawaran = Penawaran::findOrFail($id);
        $user = Auth::user();

        if ($user->jabatan === 'sales') {
            $sales = Sales::where('user_id', $user->id)->first();

            if (!$sales || $penawaran->sales_id !== $sales->id || $penawaran->status !== 'diajukan') {
                abort(403, 'Unauthorized');
            }

            // Karena relasi hasMany with cascade delete (atau jika belum, hapus manual details)
            PenawaranDetail::where('penawaran_id', $penawaran->id)->delete();
            $penawaran->delete();

            // ambil nomor halaman dari request, default 1
            $page = $request->input('page', 1);

            return redirect()->route('penawaran.sales.index', ['page' => $page])
                             ->with('success', 'Penawaran berhasil dihapus.');
        }

        abort(403, 'Unauthorized');
    }

    // Tampilkan semua penawaran (admin)
    public function adminIndex(Request $request)
    {
        $query = Penawaran::with([ 'details', 'customer', 'sales.user']);
        // Filter berdasarkan tanggal
        if ($request->filled('tanggal')) {
            $query->whereDate('tanggal_penawaran', $request->tanggal);
        }

        // Filter berdasarkan sales
        if ($request->filled('sales_id')) {
            $query->where('sales_id', $request->sales_id);
        }

        $penawaran = $query->orderBy('created_at','desc')->paginate(5);

        $sales = Sales::with('user')->get();
        return view('penawaran.index', compact('penawaran', 'sales'));
    }

    // Form ubah status penawaran (ADMIN)
public function adminEdit(Request $request, $id)
{
    // opsional tapi bagus: pastikan hanya admin yang boleh
    $user = Auth::user();
    if ($user->jabatan !== 'admin') {
        abort(403, 'Unauthorized');
    }

    $penawaran = Penawaran::with(['details', 'customer', 'sales'])->findOrFail($id);
    $page = $request->input('page', 1);

    return view('penawaran.edit', compact('penawaran', 'page'));
}



    // Update status penawaran (admin)
    public function adminUpdate(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:diajukan,diterima',
            'keterangan' => 'nullable|string'
        ]);

        $penawaran = Penawaran::with('details')->findOrFail($id);

        $penawaran->update([
            'status' => $request->status,
            'keterangan' => $request->keterangan
        ]);

        // buat SO jika status diterima
        if ($request->status === 'diterima') {
            $sudahAdaSO = SalesOrder::where('keterangan', 'like', '%' . $penawaran->nomor_penawaran . '%')->exists();

            if (!$sudahAdaSO) {
                DB::beginTransaction();
                try {
                    // totalSo mengikuti total_harga di penawaran (sudah termasuk diskon/PPN kalau ada)
                    $totalSo = $penawaran->total_harga ?? 0;

                    // buat header SO sekali
                    $so = SalesOrder::create([
                        'nomor_so'      => $this->generateNomorSO(),
                        'tanggal'       => now(),
                        'sales_id'      => $penawaran->sales_id,
                        'customer_id'   => $penawaran->customer_id,
                        'total_harga'   => $totalSo,
                        'sisa_tagihan'  => $totalSo,
                        'status'        => 'pending',
                        'keterangan'    => 'Otomatis dari penawaran ' . $penawaran->nomor_penawaran,
                    ]);

                    // insert details ke sales_order_details
                    $now = now();
                    $insertRows = [];
                    foreach ($penawaran->details as $detail) {
                        // ikuti data di penawaran_detail apa adanya
                        $agreedPrice = $detail->harga_kesepakatan ?? 0;
                        $subtotal    = $detail->subtotal ?? ($detail->qty * $agreedPrice);

                        $insertRows[] = [
                            'sales_order_id' => $so->id,
                            'product_id'     => $detail->product_id,
                            'product_name'   => $detail->product_name,
                            'qty'            => $detail->qty,
                            'harga_satuan'   => $agreedPrice,
                            'subtotal'       => $subtotal,
                            'created_at'     => $now,
                            'updated_at'     => $now,
                        ];
                    }

                    if (!empty($insertRows)) {
                        DB::table('sales_order_details')->insert($insertRows);
                    }

                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollBack();
                    \Log::error('Error creating SO from penawaran: '.$e->getMessage());
                    return back()->withErrors('Gagal membuat Sales Order otomatis: '.$e->getMessage());
                }
            }
        }

        return redirect()->route('penawaran.index', ['page' => $request->page])
                     ->with('success', 'Status penawaran diperbarui.');
    }

    public function cetakPerPenawaran($id)
    {
        $user = Auth::user();

        if ($user->jabatan !== 'sales') {
            abort(403, 'Unauthorized');
        }

        $sales = Sales::where('user_id', $user->id)->firstOrFail();

        $penawaran = Penawaran::with(['customer', 'details'])
            ->where('id', $id)
            ->where('sales_id', $sales->id)
            ->where('status', 'diterima') // hanya yang sudah diterima
            ->firstOrFail();

        $pdf = Pdf::loadView('penawaran.pdf.penawaran', [
            'penawaran' => $penawaran,
            'sales'     => $sales,
            'user'      => $user,
        ])->setPaper('A4', 'portrait');

        $filename = 'Penawaran-' . $penawaran->nomor_penawaran . '.pdf';

        return $pdf->stream($filename);
    }

    public function cetakBulanan(Request $request)
{
    $user = Auth::user();

    if ($user->jabatan !== 'sales') {
        abort(403, 'Unauthorized');
    }

    $sales = Sales::where('user_id', $user->id)->firstOrFail();

    // Ambil bulan & tahun dari filter tanggal (kalau kosong pakai bulan ini)
    if ($request->filled('tanggal')) {
        $date = Carbon::parse($request->tanggal);
    } else {
        $date = now();
    }

    $month = $date->month;
    $year  = $date->year;

    $query = Penawaran::with(['customer', 'details'])
        ->where('sales_id', $sales->id)
        ->whereYear('tanggal_penawaran', $year)
        ->whereMonth('tanggal_penawaran', $month);

    // filter customer kalau diisi
    if ($request->filled('customer_id')) {
        $query->where('customer_id', $request->customer_id);
    }

    $penawaran = $query->orderBy('tanggal_penawaran')->get();

    $customer = null;
    if ($request->filled('customer_id')) {
        $customer = Customer::find($request->customer_id);
    }

    $pdf = Pdf::loadView('penawaran.pdf.bulanan', [
        'penawaran' => $penawaran,
        'sales'     => $sales,
        'user'      => $user,
        'bulan'     => $month,
        'tahun'     => $year,
        'customer'  => $customer,
    ])->setPaper('A4', 'landscape');

    $filename = 'Penawaran-' . $sales->id . '-' . $year . sprintf('%02d', $month) . '.pdf';

    return $pdf->stream($filename);
}

}
