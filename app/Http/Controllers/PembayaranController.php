<?php

namespace App\Http\Controllers;

use App\Pembayaran;
use App\SalesOrder;
use App\Customer;
use App\Sales;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PembayaranController extends Controller
{
    // Tampilkan daftar pembayaran
    public function index(Request $request)
{
    $user = Auth::user();
    $query = Pembayaran::with(['salesOrder.customer']);

    // 🔍 Jika user adalah sales
    if ($user->jabatan === 'sales') {
        $sales = Sales::where('user_id', $user->id)->first();

        // Filter berdasarkan sales yang login
        $query->whereHas('salesOrder', function ($q) use ($sales) {
            $q->where('sales_id', $sales->id);
        });

        // Filter input dari form (khusus sales)
        if ($request->filled('tanggal')) {
            $query->whereDate('tanggal_pembayaran', $request->tanggal);
        }
 
        $customers = $sales->customers ?? collect();
        
    } else {

        // 🔍 Jika admin/manajer
        if ($request->filled('tanggal')) {
        $query->whereDate('tanggal_pembayaran', $request->tanggal);
    }

    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }
        $customers = Customer::all();
        $sales = Sales::with('user')->get();
    }

    $pembayaran = $query->orderBy('created_at', 'desc')
                        ->paginate(5)
                        ->appends($request->all());

    return view('pembayaran.index', compact('pembayaran', 'customers'));

}


    // Form input pembayaran
    public function create()
{
    $user = Auth::user();

    // Kalau yang login adalah SALES
    if ($user->jabatan === 'sales') {
        $sales = Sales::where('user_id', $user->id)->firstOrFail();

        $salesOrders = SalesOrder::where('sales_id', $sales->id)
            ->where('sisa_tagihan', '>', 0)          // masih ada tagihan
            ->where('status', '!=', 'lunas')         // bukan yang sudah lunas
            ->orderBy('tanggal', 'desc')
            ->get();
    } 
    // Kalau ADMIN / MANAGER
    else {
        $salesOrders = SalesOrder::where('sisa_tagihan', '>', 0)
            ->where('status', '!=', 'lunas')
            ->orderBy('tanggal', 'desc')
            ->get();
    }

    return view('pembayaran.create', compact('salesOrders'));
}



    // Simpan data pembayaran
    public function store(Request $request)
    {
        $request->validate([
            'sales_order_id' => 'required|exists:sales_orders,id',
            'tanggal_pembayaran' => 'required|date',
            'jumlah' => 'required|numeric|min:0',
            'metode_pembayaran' => 'required|in:cash,transfer,tempo',
            'bukti' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'catatan' => 'nullable|string'
        ]);
        

        $bukti = null;
        if ($request->hasFile('bukti')) {
            $bukti = $request->file('bukti')->store('bukti_pembayaran', 'public');
        }


        Pembayaran::create([
            'sales_order_id' => $request->sales_order_id,
            'tanggal_pembayaran' => $request->tanggal_pembayaran,
            'jumlah' => $request->jumlah,
            'metode_pembayaran' => $request->metode_pembayaran,
            'bukti' => $bukti,
            'status' => 'pending',
            'catatan' => $request->catatan
        ]);

        return redirect()->route('pembayaran.index')->with('success', 'Pembayaran berhasil ditambahkan.');
    }


    // Tampilkan semua pembayaran (ADMIN)
    public function adminIndex()
    {
    $pembayaran = Pembayaran::with(['salesOrder', 'salesOrder.sales', 'salesOrder.customer'])
    ->orderBy('created_at', 'desc')
    ->paginate(5);
    return view('pembayaran.admin.index', compact('pembayaran'));
    }

    // Form verifikasi pembayaran (ADMIN)
    public function adminEdit($id)
    {
    $pembayaran = Pembayaran::with('salesOrder')->findOrFail($id);
    return view('pembayaran.admin.edit', compact('pembayaran'));
    }


    // Proses update/verifikasi pembayaran (ADMIN)
    public function adminUpdate(Request $request, $id)
    {
    $request->validate([
        'status' => 'required|in:diterima',
        'catatan' => 'nullable|string'
    ]);

    $pembayaran = Pembayaran::with('salesOrder')->findOrFail($id);
    $salesOrder = $pembayaran->salesOrder;

    // Cek apakah status sebelumnya belum diterima dan sekarang menjadi diterima
    $isNewlyAccepted = $pembayaran->status !== 'diterima' && $request->status === 'diterima';

    if ($isNewlyAccepted) {
    // Validasi: apakah jumlah melebihi sisa tagihan
    if ($pembayaran->jumlah > $salesOrder->sisa_tagihan) {
        return back()->withErrors(['status' => 'Jumlah pembayaran melebihi sisa tagihan.']);
    }

    // Update sisa tagihan sementara
    $salesOrder->sisa_tagihan -= $pembayaran->jumlah;
    if ($salesOrder->sisa_tagihan < 0) {
        $salesOrder->sisa_tagihan = 0;
    }

    // ✅ Tambahkan logika ini:
        if ($salesOrder->sisa_tagihan == 0) {
            $salesOrder->status = 'lunas';
        } elseif ($salesOrder->status === 'pending') {
    // Misalnya jika sebelumnya pending tapi baru sebagian dibayar → ganti jadi diproses
    $salesOrder->status = 'diproses';
}

    // Hitung total pembayaran diterima
    $totalDibayar = Pembayaran::where('sales_order_id', $salesOrder->id)
        ->where('status', 'diterima')
        ->sum('jumlah') + $pembayaran->jumlah;

    // Update status jika lunas
    if ($totalDibayar >= $salesOrder->total_harga && $salesOrder->status !== 'selesai') {
    $salesOrder->sisa_tagihan = 0;

    // Set status menjadi 'lunas' hanya jika belum selesai
    if ($salesOrder->status !== 'lunas') {
        $salesOrder->status = 'lunas';
    }
}


    // Jika ini pembayaran pertama
    $jumlahDiterima = Pembayaran::where('sales_order_id', $salesOrder->id)
        ->where('status', 'diterima')
        ->count();

    if ($jumlahDiterima == 0) {
        $salesOrder->tanggal_pengiriman = \Carbon\Carbon::parse($pembayaran->tanggal_pembayaran)->addDay();
    }

    // Simpan perubahan ke Sales Order
    $salesOrder->save();

    // ✅ Otomatisasi ke tabel penjualan jika status sudah lunas
    if ($salesOrder->status === 'lunas') {
    // Cek apakah sudah pernah dibuat di tabel penjualan
    $sudahAda = \App\Penjualan::where('sales_id', $salesOrder->id)->exists();

    if (!$sudahAda) {

        // Buat entri penjualan baru
        \App\Penjualan::create([
        'sales_order_id' => $salesOrder->id,
        'sales_id' => $salesOrder->sales_id,
        'customer_id' => $salesOrder->customer_id,
        'total_harga' => $salesOrder->total_harga,
        'nomor_faktur' => $this->generateNomorPenjualan(),
        'tanggal_pelunasan' => now()->setTimezone('Asia/Jakarta'), // ✅ tanggal penjualan = tanggal pelunasan terakhir
        ]);
            }
        }
    }

    // Update status & catatan pembayaran
    $pembayaran->update([
        'status' => $request->status,
        'catatan' => $request->catatan
    ]);

        return redirect()->route('pembayaran.admin.index', ['page' => $request->input('page', 1)])
    ->with('success', 'Status pembayaran berhasil diperbarui.' . ($isNewlyAccepted ? ' Sales order siap dikirim dan tanggal pengiriman diatur.' : ''));


}

// 🔢 Generate nomor faktur penjualan otomatis
private function generateNomorPenjualan()
{
    $prefix = 'PNJ-';
    $today = now()->format('Ymd');

    $last = \App\Penjualan::where('nomor_faktur', 'like', $prefix . $today . '%')
        ->orderBy('nomor_faktur', 'desc')
        ->first();

    $next = $last ? str_pad(((int)substr($last->nomor_faktur, -3)) + 1, 3, '0', STR_PAD_LEFT) : '001';

    return $prefix . $today . $next;
}


}
