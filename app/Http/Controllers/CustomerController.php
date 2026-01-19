<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Customer;
use App\User;
use App\Sales;

class CustomerController extends Controller
{
    // Menampilkan data customer dengan pagination dan pencarian
    public function index(Request $request)
    {
    $query = Customer::query();

    // Batasi hanya untuk sales
    if (Auth::user()->jabatan === 'sales') {
        $query->where('user_id', Auth::id()); // ⬅️ hanya data miliknya sendiri
    }

    // Fitur pencarian
    if ($request->has('search') && $request->search != '') {
        $query->where('nama_customer', 'like', '%' . $request->search . '%');
    }

    $customers = $query->orderBy('created_at', 'desc')->paginate(03)->withQueryString();

    return view('master.customer.index', compact('customers'));
    }

    // Form untuk menambahkan customer baru
    public function create()
    {
        return view('master.customer.create');
    }

    // Menyimpan data customer baru
    public function store(Request $request)
    {
    $request->validate([
        'nama_customer' => 'required',
        'alamat' => 'required',
        'telepon' => 'nullable',
        'status_customer' => 'required|in:baru,lama'
    ]);

    Customer::create([
        'user_id' => Auth::id(), // user yang membuat data
        'nama_customer' => $request->nama_customer,
        'alamat' => $request->alamat,
        'telepon' => $request->telepon,
        'status_customer' => $request->status_customer,
    ]);

    return redirect()->route('customer.index')
                     ->with('success', 'Customer berhasil ditambahkan.');
    }

    // Form untuk mengedit data customer
    public function edit(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);

        $page = $request->input('page', 1);

        return view('master.customer.edit', compact('customer','page'));
    }

    // Menyimpan perubahan data customer
    public function update(Request $request, $id)
    {
    $customer = Customer::findOrFail($id);
    $customer->update([
        'telepon' => $request->telepon,
    ]);

    // $customer->update($request->all());
    $page = $request->input('page', 1);

    return redirect()->route('customer.index', ['page' => $page])
                     ->with('success', 'Customer berhasil diperbarui.');
    }

    // Menghapus data customer
    public function destroy(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);
        $customer->delete();

        $page = $request->input('page', 1);

        return redirect()->route('customer.index', ['page' => $page])
                         ->with('success', 'Customer berhasil dihapus.');
    }

    // ========== ADMIN INDEX ==========

    // Menampilkan data customer dengan pagination, pencarian, dan filter sales untuk admin
    public function adminIndex(Request $request)
    {
        // query customer + relasi user (sales)
        $query = Customer::with('user');

        // Pencarian nama customer
        if ($request->has('search') && $request->search != '') {
            $query->where('nama_customer', 'like', '%' . $request->search . '%');
        }

        // Filter berdasarkan sales (dropdown kirim sales_id = id di tabel sales)
        if ($request->filled('sales_id')) {
            $sales = Sales::find($request->sales_id);

            if ($sales) {
                // di tabel customer kolomnya user_id (id dari tabel users)
                $query->where('user_id', $sales->user_id);
            }
        }

        $customers = $query
            ->orderBy('created_at', 'desc')
            ->paginate(10)               // boleh 5 / 10 sesuai selera
            ->withQueryString();

        // data untuk dropdown sales
        $sales = Sales::all()->sortBy(function ($s) {
        return intval(preg_replace('/[^0-9]/', '', $s->nama_sales));
        });


        return view('master.customer.admin.index', compact('customers', 'sales'));
    }


    // Form untuk menambahkan customer baru oleh admin
    public function adminCreate()
    {
        $sales = User::where('jabatan', 'sales')->get();

        return view('master.customer.admin.create', compact('sales'));
    }

    // Menyimpan data customer baru oleh admin
    public function adminStore(Request $request)
    {
        $request->validate([
            'nama_customer' => 'required|string|max:255',
            'alamat' => 'required|string',
            'telepon' => 'required|string',
            'status_customer' => 'required|in:baru,lama',
            'user_id' => 'required|exists:users,id'
        ]);

        Customer::create([
            'nama_customer' => $request->nama_customer,
            'alamat' => $request->alamat,
            'telepon' => $request->telepon,
            'status_customer' => $request->status_customer,
            'user_id' => $request->user_id,
        ]);

        return redirect()->route('customer.admin.index')
                         ->with('success', 'Customer berhasil ditambahkan oleh Admin.');
    }

    // ========== ADMIN EDIT ==========

    // Form untuk edit customer pada admin
    public function adminEdit(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);
        $sales = User::where('jabatan', 'sales')->get();

        $page = $request->input('page', 1);

        return view('master.customer.admin.edit', compact('customer', 'sales', 'page'));
    }

    public function adminUpdate(Request $request, $id)
    {
        $request->validate([
            'nama_customer' => 'required|string|max:255',
            'alamat' => 'required|string',
            'telepon' => 'required|string',
            'user_id' => 'required|exists:users,id',
            'status_customer' => 'required|in:baru,lama'
        ]);

        $customer = Customer::findOrFail($id);

        $customer->update([
            'nama_customer' => $request->nama_customer,
            'alamat' => $request->alamat,
            'telepon' => $request->telepon,
            'user_id' => $request->user_id,
            'status_customer' => $request->status_customer
        ]);

        $page = $request->input('page', 1);

        return redirect()->route('customer.admin.index', ['page' => $page])
                         ->with('success', 'Customer berhasil diperbarui oleh Admin.');
    }

    // Menghapus data customer oleh admin
    public function adminDestroy(Request $request, $id)
    {
    $customer = Customer::findOrFail($id);
    $customer->delete();

    $page = $request->input('page', 1);

    return redirect()->route('customer.admin.index', ['page' => $page])
                         ->with('success', 'Customer berhasil dihapus oleh Admin.');
    }

    // ========== MANAGER INDEX ==========

    // Menampilkan data customer dengan pagination, pencarian, dan filter sales untuk admin
    public function managerIndex(Request $request)
    {
        // query customer + relasi user (sales)
        $query = Customer::with('user');

        // Pencarian nama customer
        if ($request->has('search') && $request->search != '') {
            $query->where('nama_customer', 'like', '%' . $request->search . '%');
        }

        // Filter berdasarkan sales (dropdown kirim sales_id = id di tabel sales)
        if ($request->filled('sales_id')) {
            $sales = Sales::find($request->sales_id);

            if ($sales) {
                // di tabel customer kolomnya user_id (id dari tabel users)
                $query->where('user_id', $sales->user_id);
            }
        }

        $customers = $query
            ->orderBy('created_at', 'desc')
            ->paginate(3)               // boleh 5 / 10 sesuai selera
            ->withQueryString();

        // data untuk dropdown sales
        $sales = Sales::all()->sortBy(function ($s) {
        return intval(preg_replace('/[^0-9]/', '', $s->nama_sales));
        });


        return view('master.customer.manager.index', compact('customers', 'sales'));
    }

}
