<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\SalesOrder;
use App\Produk;
use App\Sales;
use App\Customer;
use App\Penjualan;
use Barryvdh\DomPDF\Facade\Pdf;


class SalesOrderController extends Controller
{
    // 1. Menampilkan semua Sales Order (untuk admin)
    public function index(Request $request)
{
    $query = SalesOrder::with(['sales', 'customer', 'pembayaran', 'details.produk']);

    if ($request->filled('tanggal')) {
        $query->whereDate('tanggal', $request->tanggal);
    }
    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }
    if ($request->filled('sales_id')) {
        $query->where('sales_id', $request->sales_id);
    }
    if ($request->filled('customer_id')) {
        $query->where('customer_id', $request->customer_id);
    }

    $orders = $query->orderBy('created_at', 'desc')->paginate(5);

    $allSales     = Sales::all();
    $allCustomers = Customer::all();

    return view('sales_order.admin_index', compact('orders', 'allSales', 'allCustomers'));
}


    // 2. Menampilkan Sales Order milik sales yang sedang login
    public function myOrders(Request $request)
{
    $sales = Sales::where('user_id', Auth::id())->first();

    if (!$sales) {
        return redirect()->back()->with('error', 'Data Sales tidak ditemukan.');
    }

    $query = SalesOrder::with(['customer', 'details.produk'])
        ->where('sales_id', $sales->id)
        ->orderBy('created_at', 'desc');

    if ($request->filled('tanggal')) {
        $query->whereDate('tanggal', $request->tanggal);
    }
    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }
    if ($request->filled('customer_id')) {
        $query->where('customer_id', $request->customer_id);
    }

    $orders = $query->paginate(5)->appends($request->all());

    $customers = Customer::where('user_id', Auth::id())->get();

    return view('sales_order.sales.index', compact('orders', 'customers'));
}

private function generateNomorSO(){
    $prefix = 'SO-';
    // Format tanggal hari ini
    $today = now()->format('Ymd');
    $last = SalesOrder::where('nomor_so', 'like', $prefix . $today . '%')->orderBy('nomor_so', 'desc')->first();
    $next = $last ? str_pad(((int)substr($last->nomor_so, -3)) + 1, 3, '0', STR_PAD_LEFT) : '001';
    return $prefix . $today . $next;
}

    // 3. Menampilkan form input SO (untuk sales)
    public function create()
    {
        $user = Auth::user();
        $sales = Sales::where('user_id', $user->id)->first();

        $produk = Produk::all();

        $customers = Customer::where('user_id', $user->id)
        ->where('status_customer', 'lama')
        ->orderBy('nama_customer')
        ->get();
        
        return view('sales_order.sales.create', compact('produk', 'customers'));
    }

    // 4. Menyimpan Sales Order (oleh sales)

    public function store(Request $request)
    {
        $sales = Sales::where('user_id', Auth::id())->first();
        if (!$sales) {
            return redirect()->back()->withErrors('Sales tidak ditemukan. Hubungi admin.');
        }

        // kalau masih ada form lama single item
        if (!$request->has('items') && $request->filled('produk_id')) {
            $single = [
                'product_id'   => $request->input('produk_id'),
                'qty'          => $request->input('jumlah'),
                'harga_satuan' => $request->input('harga_satuan'),
                'note'         => $request->input('note') ?? null,
            ];
            $request->merge(['items' => [$single]]);
        }

        // VALIDASI
        $request->validate([
            'tanggal'              => 'required|date',
            'customer_id'          => 'required|exists:customers,id',
            'items'                => 'required|array|min:1',
            'items.*.product_id'   => 'required|integer|exists:produk,id',
            'items.*.qty'          => 'required|integer|min:1',
            'items.*.harga_satuan' => 'required|numeric|min:0',
            'items.*.note'         => 'nullable|string',
            'diskon_global_pct'    => 'nullable|numeric|min:0|max:10',
        ]);

        DB::beginTransaction();
        try {
            // baca & clamp diskon 0–10%
            $diskonPct = (float)$request->input('diskon_global_pct', 0);
            if ($diskonPct < 0)  $diskonPct = 0;
            if ($diskonPct > 10) $diskonPct = 10;

            // buat header dulu dengan nilai 0 (nanti di-update)
            $so = SalesOrder::create([
                'nomor_so'          => $this->generateNomorSO(),
                'tanggal'           => $request->tanggal,
                'sales_id'          => $sales->id,
                'customer_id'       => $request->customer_id,
                'total_bruto'       => 0,
                'diskon_global_pct' => $diskonPct,
                'diskon_global_rp'  => 0,
                'ppn_rp'            => 0,
                'total_harga'       => 0,
                'sisa_tagihan'      => 0,
                'status'            => 'pending',
                'keterangan'        => $request->keterangan ?? null,
            ]);

            // hitung total dari detail
            $totalBruto = 0;

            foreach ($request->items as $it) {
                $product = Produk::find($it['product_id']);

                $qty     = (int)$it['qty'];
                $harga   = (float)$it['harga_satuan'];
                $subtotal= $qty * $harga;

                $totalBruto += $subtotal;

                \App\SalesOrderDetail::create([
                    'sales_order_id' => $so->id,
                    'product_id'     => $product->id,
                    'product_name'   => $product->nama_produk,
                    'satuan'         => $product->satuan ?? null,
                    'qty'            => $qty,
                    'harga_satuan'   => $harga,
                    'subtotal'       => $subtotal,
                    'note'           => $it['note'] ?? null,
                ]);
            }

            // hitung diskon + ppn + grand total
            $diskonRp   = round($totalBruto * $diskonPct / 100);
            $dpp        = $totalBruto - $diskonRp;
            $ppnRp      = round($dpp * 0.11);
            $grandTotal = $dpp + $ppnRp;

            // update header
            $so->update([
                'total_bruto'       => $totalBruto,
                'diskon_global_rp'  => $diskonRp,
                'ppn_rp'            => $ppnRp,
                'total_harga'       => $grandTotal,
                'sisa_tagihan'      => $grandTotal, // otomatis sama dengan total
            ]);

            // ubah status customer baru -> lama (jaga-jaga kalau belum otomatis)
            $customer    = Customer::find($request->customer_id);
            $jumlahOrder = SalesOrder::where('customer_id', $customer->id)->count();
            if ($jumlahOrder >= 1 && strtolower($customer->status_customer) === 'baru') {
                $customer->status_customer = 'lama';
                $customer->save();
            }

            DB::commit();
            return redirect()->route('sales-order.my')
                ->with('success', 'Sales Order berhasil dibuat.');
        }
            catch (\Exception $e) {
    DB::rollBack();
    dd($e->getMessage());
        }
    }



    private function generateNomorPenjualan()
    {
    $prefix = 'PNJ-';
    $today = now()->format('Ymd');

    $last = Penjualan::where('nomor_faktur', 'like', $prefix . $today . '%')
        ->orderBy('nomor_faktur', 'desc')
        ->first();

    $next = $last ? str_pad(((int)substr($last->nomor_faktur, -3)) + 1, 3, '0', STR_PAD_LEFT) : '001';

    return $prefix . $today . $next;
    }


    // 7. Mengubah SO menjadi Penjualan
    public function convertToPenjualan($id)
{
    $so = SalesOrder::with('details')->findOrFail($id);

    // cek lunas
    $sisaTagihan = $so->total_harga - $so->pembayaran()
        ->where('status', 'diterima')
        ->sum('jumlah');

    if ($sisaTagihan > 0) {
        return back()->with('error', 'Sales Order belum lunas. Tidak bisa diproses.');
    }

    if (!in_array($so->status, ['diproses', 'lunas'])) {
        return back()->with('error', 'Sales Order belum bisa diproses.');
    }

    if ($so->status === 'selesai') {
        return back()->with('error', 'Sales Order sudah dikonversi sebelumnya.');
    }

    $firstDetail = $so->details->first();
    $totalQty    = $so->details->sum('qty');

    Penjualan::create([
        'nomor_faktur'  => $this->generateNomorPenjualan(),
        'tanggal'       => now(),
        'sales_id'      => $so->sales_id,
        'customer_id'   => $so->customer_id,
        'produk_id'     => $firstDetail->product_id ?? null,   // representatif
        'jumlah'        => $totalQty,
        'harga_satuan'  => $firstDetail->harga_satuan ?? 0,
        'total_harga'   => $so->total_harga,                   // sudah termasuk diskon + PPN
        'sales_order_id'=> $so->id ?? null,                    // kalau kolom ini ada
    ]);

    $so->status = 'selesai';
    $so->save();

    return back()->with('success', 'Sales Order berhasil diproses menjadi Penjualan.');
}

// CETAK SATU SALES ORDER (ADMIN SAJA)
public function print($id)
{
    $user = Auth::user();

    // Hanya admin yang boleh cetak dari menu ini
    if ($user->jabatan !== 'admin') {
        abort(403, 'Unauthorized');
    }

    $order = SalesOrder::with([
            'sales',
            'customer',
            'details.produk',    
        ])->findOrFail($id);

    $pdf = Pdf::loadView('sales_order.pdf.sales_order', [
        'order' => $order,
        'user'  => $user,
    ])->setPaper('A4', 'portrait');

    $filename = 'SO-' . ($order->nomor_so ?? $order->id) . '.pdf';

    return $pdf->stream($filename);
}

}
