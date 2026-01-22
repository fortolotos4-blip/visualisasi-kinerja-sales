<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Penjualan;
use App\Sales;
use App\Produk;
use App\Customer;
class PenjualanManagerController extends Controller
{

public function manager(Request $request)
{
    $query = Penjualan::with(['sales', 'customer', 'produk']);

    // Filter berdasarkan tanggal
    if ($request->filled('tanggal')) {
        $query->whereDate('tanggal', $request->tanggal);
    }

    // Filter berdasarkan sales
    if ($request->filled('sales_id')) {
        $query->where('sales_id', $request->sales_id);
    }

    // Filter berdasarkan customer
    if ($request->filled('customer_id')) {
        $query->where('customer_id', $request->customer_id);
    }

    $penjualan = $query->orderBy('created_at','desc')->paginate(5); // tampilkan 10 data per halaman

    // Tambahkan status "Lunas" ke setiap item
        $penjualan->getCollection()->transform(function ($item) {
            $item->status = 'Lunas';
            return $item;
        });

    $sales = Sales::all();
    $customers = Customer::all();

    return view('penjualan.manager', compact('penjualan', 'sales', 'customers'));
}
}

