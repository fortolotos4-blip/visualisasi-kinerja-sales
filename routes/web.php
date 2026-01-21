<?php
use App\Http\Controllers\PenjualanManagerController;
use App\Produk;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProdukController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\WilayahController;
use App\Http\Controllers\PenjualanController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\DashboardSalesController;
use App\Http\Controllers\KunjunganSalesController;
use App\Http\Controllers\PenawaranController;
use App\Http\Controllers\PembayaranController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TargetSalesController;
use App\Http\Controllers\DashboardManagerController;
use App\Http\Controllers\KontribusiParameterController;
use App\Http\Controllers\SalesManagerController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return redirect()->route('login');
});


// Registrasi 
Route::get('/register', [AuthController::class, 'showRegister']);
Route::post('/register', [AuthController::class, 'register']);

// login 
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

// Logout
Route::get('/logout', [AuthController::class, 'logout']);

// Dashboard Sales
Route::middleware('auth')->group(function () {
    Route::get('/dashboard/sales', [DashboardSalesController::class, 'index'])->name('dashboard.sales');
});

// Dasboard Admin
Route::middleware('auth')->group(function () {
    Route::get('/dashboard/admin', [DashboardController::class, 'index'])->name('dashboard.admin');
});

// Dasboard Manajer
Route::middleware('auth')->group(function () {
    Route::get('/dashboard/manajer', [DashboardManagerController::class, 'index'])->name('dashboard.manager');
});

// Produk
Route::prefix('produk')->middleware('auth')->group(function () {
    Route::get('/', [ProdukController::class, 'index'])->name('produk.index');
    Route::get('/create', [ProdukController::class, 'create'])->name('produk.create');
    Route::post('/store', [ProdukController::class, 'store'])->name('produk.store');

    Route::get('/{id}/edit', [ProdukController::class, 'edit'])->name('produk.edit');
    Route::put('/{id}', [ProdukController::class, 'update'])->name('produk.update');
    Route::delete('/{id}', [ProdukController::class, 'destroy'])->name('produk.destroy');
});

// Customer
// Route bagian sales
Route::prefix('customer')->middleware(['auth', 'sales.active'])->group(function () {
    Route::get('/', [CustomerController::class, 'index'])->name('customer.index');
    Route::get('/customer/create', [CustomerController::class, 'create'])->name('customer.create');
    Route::post('/customer/store', [CustomerController::class, 'store'])->name('customer.store');
    Route::get('/customer/{id}/edit', [CustomerController::class, 'edit'])->name('customer.edit');
    Route::put('/customer/{id}', [CustomerController::class, 'update'])->name('customer.update');
    Route::delete('/customer/{id}', [CustomerController::class, 'destroy'])->name('customer.destroy');
});
// Route bagian admin
Route::prefix('customer')->middleware(['auth'])->group(function () {
    Route::get('/admin', [CustomerController::class, 'adminIndex'])->name('customer.admin.index');
    Route::get('/create', [CustomerController::class, 'adminCreate'])->name('customer.admin.create');
    Route::post('/store', [CustomerController::class, 'adminStore'])->name('customer.admin.store');
    Route::get('/{id}/edit-admin', [CustomerController::class, 'adminEdit'])->name('customer.admin.edit');
    Route::put('/{id}/update-admin', [CustomerController::class, 'adminUpdate'])->name('customer.admin.update');
    Route::delete('/{id}', [CustomerController::class, 'adminDestroy'])->name('customer.admin.destroy');
});
// Route bagian manager
Route::prefix('customer')->middleware(['auth'])->group(function () {
    Route::get('/manager', [CustomerController::class, 'managerIndex'])->name('customer.manager.index');
});

// Sales
Route::prefix('sales')->middleware('auth')->group(function () {
    Route::get('/', [SalesController::class, 'index'])->name('sales.index');
    Route::get('/create', [SalesController::class, 'create'])->name('sales.create');
    Route::post('/store', [SalesController::class, 'store'])->name('sales.store');

    Route::get('/{id}/edit', [SalesController::class, 'edit'])->name('sales.edit');
    Route::put('/{id}', [SalesController::class, 'update'])->name('sales.update');
    Route::delete('/{id}', [SalesController::class, 'destroy'])->name('sales.destroy');
});

//Status Sales
Route::patch('/sales/{id}/toggle-status', 
    [SalesController::class, 'toggleStatus']
)->name('sales.toggle-status');


//Manager
Route::prefix('manager')->middleware(['auth' /*, 'role:manager' */])->group(function () {
    Route::get('/sales', [SalesManagerController::class, 'index'])->name('master.sales.manager.index');
    Route::post('/sales/{id}/promote', [SalesManagerController::class, 'promote'])->name('master.sales.manager.promote');
});

//Wilayah
Route::prefix('wilayah')->middleware('auth')->group(function () {
    Route::get('/', [WilayahController::class, 'index'])->name('wilayah.index');
    Route::get('/create', [WilayahController::class, 'create'])->name('wilayah.create');
    Route::post('/store', [WilayahController::class, 'store'])->name('wilayah.store');

    Route::get('/{id}/edit', [WilayahController::class, 'edit'])->name('wilayah.edit');
    Route::put('/{id}', [WilayahController::class, 'update'])->name('wilayah.update');
    Route::delete('/{id}', [WilayahController::class, 'destroy'])->name('wilayah.destroy');
});

//Target Sales
Route::prefix('manajer')->middleware(['auth'])->group(function () {
    Route::get('/target-sales', [TargetSalesController::class, 'index'])->name('target_sales.index');
    Route::get('/target-sales/create', [TargetSalesController::class, 'create'])->name('target_sales.create');
    Route::post('/target-sales', [TargetSalesController::class, 'store'])->name('target_sales.store');

    // batal untuk seluruh tahun (tetap)
    Route::post('/target-sales/{tahun}/batal', [TargetSalesController::class, 'batal'])->name('target_sales.batal');

    // tambahan: update per sales (override) dan reset (hapus override sehingga balik ke default)
    Route::post('/target-sales/{sales}/update', [TargetSalesController::class, 'update'])->name('target_sales.update');
    Route::post('/target-sales/{sales}/reset', [TargetSalesController::class, 'reset'])->name('target_sales.reset');
});

// Indikator Penilaian Kinerja
Route::prefix('manajer')->middleware(['auth'])->group(function () {
    // Kontribusi Parameter
    Route::get('/kontribusi-parameter', [KontribusiParameterController::class, 'index'])->name('kontribusi_parameters.index');
    Route::get('/kontribusi-parameter/create', [KontribusiParameterController::class, 'create'])->name('kontribusi_parameters.create');
    Route::post('/kontribusi-parameter', [KontribusiParameterController::class, 'store'])->name('kontribusi_parameters.store');
    Route::post('/kontribusi-parameter/{periode_tahun}/batal',[KontribusiParameterController::class, 'batal'])->name(('kontribusi_parameters.batal'));
});

// Kunjungan Sales
Route::middleware(['auth', 'sales.active'])->group(function () {
    Route::get('/kunjungan', [KunjunganSalesController::class, 'index'])->name('kunjungan.index');
    Route::get('/kunjungan/create', [KunjunganSalesController::class, 'create'])->name('kunjungan.create');
    Route::post('/kunjungan', [KunjunganSalesController::class, 'store'])->name('kunjungan.store');
});

// Penawaran
//Route untuk sales
Route::middleware(['auth', 'sales.active'])->group(function () {
    Route::get('/penawaran', [PenawaranController::class, 'index'])->name('penawaran.sales.index');
    Route::get('/penawaran/create', [PenawaranController::class, 'create'])->name('penawaran.sales.create');
    Route::post('/penawaran', [PenawaranController::class, 'store'])->name('penawaran.store');

    Route::get('/penawaran/{id}/edit', [PenawaranController::class, 'edit'])->name('penawaran.sales.edit');
    Route::put('/penawaran/{id}', [PenawaranController::class, 'update'])->name('penawaran.sales.update');

    Route::post('/penawaran/{id}/convert', [PenawaranController::class, 'convert'])->name('penawaran.sales.convert');
    Route::post('/penawaran/{id}/reject', [PenawaranController::class, 'reject'])->name('penawaran.sales.reject');

    Route::get('/penawaran/{id}/cetak', [PenawaranController::class, 'cetakPerPenawaran'])
        ->name('penawaran.sales.cetak');

    Route::get('/penawaran/cetak-bulanan', [PenawaranController::class, 'cetakBulanan'])
        ->name('penawaran.sales.cetak.bulanan');
});


// Route untuk Admin
Route::prefix('admin')->middleware(['auth'])->group(function () {
    Route::get('/penawaran', [PenawaranController::class, 'adminIndex'])->name('penawaran.index');
    Route::get('/penawaran/{id}/edit', [PenawaranController::class, 'adminEdit'])->name('penawaran.edit');
    Route::put('/penawaran/{id}', [PenawaranController::class, 'adminUpdate'])->name('penawaran.update');
});

// ambil harga satuan produk untuk penawaran
Route::get('/get-harga-produk/{id}', function ($id) {
    $produk = Produk::find($id);
    return response()->json([
        'harga_satuan' => $produk ? $produk->harga : 0
    ]);
});

// Sales Order (SO)
// Sales Order bagian Sales
Route::middleware(['auth', 'sales.active'])->group(function () {
    Route::get('/my-sales-order', [SalesOrderController::class, 'myOrders'])
        ->name('sales-order.my');

    Route::get('/sales-order/create', [SalesOrderController::class, 'create'])
        ->name('sales-order.create');

    Route::post('/sales-order', [SalesOrderController::class, 'store'])
        ->name('sales-order.store');
});

Route::middleware(['auth'])->group(function () {
    // Route bagian admin
    Route::get('/sales-order', [SalesOrderController::class, 'index'])->name('sales-order.index');

    // 🔹 CETAK SATU SALES ORDER (admin)
    Route::get('/sales-order/{id}/cetak', [SalesOrderController::class, 'print'])
        ->name('sales-order.print');
});

// Pembayaran
Route::middleware(['auth', 'sales.active'])->group(function () {
    // Route untuk sales
    Route::get('/pembayaran', [PembayaranController::class, 'index'])->name('pembayaran.index');
    Route::get('/pembayaran/create', [PembayaranController::class, 'create'])->name('pembayaran.create');
    Route::post('/pembayaran', [PembayaranController::class, 'store'])->name('pembayaran.store');
});

// Route untuk Admin
Route::prefix('admin')->middleware(['auth'])->group(function () {
    Route::get('/pembayaran', [PembayaranController::class, 'adminIndex'])->name('pembayaran.admin.index');
    Route::get('/pembayaran/{id}/edit', [PembayaranController::class, 'adminEdit'])->name('pembayaran.admin.edit');
    Route::put('/pembayaran/{id}', [PembayaranController::class, 'adminUpdate'])->name('pembayaran.admin.update');
});

//Penjualan
// Route bagian admin
Route::prefix('penjualan')->middleware('auth')->group(function () {
    Route::get('/', [PenjualanController::class, 'index'])->name('penjualan.index');
});
// Route bagian manajer
Route::prefix('manajer')->middleware(['auth'])->group(function () {
    Route::get('/', [PenjualanManagerController::class, 'manager'])->name('penjualan.manager');
});

// Laporan
// Route untuk Sales
Route::middleware(['auth', 'sales.active'])->group(function () {
    Route::get('/laporan/sales', [LaporanController::class, 'laporanSales'])->name('laporan.sales');
});
// Route untuk Admin
Route::middleware(['auth'])->prefix('admin')->group(function () {
    Route::get('/laporan', [LaporanController::class, 'laporanAdmin'])->name('laporan.admin');
    Route::get('/admin/laporan', [LaporanController::class, 'laporanAdmin'])->name('laporan.admin');

});
// Route untuk Manajer
Route::middleware(['auth'])->prefix('manajer')->group(function () {
    Route::get('/laporan', [LaporanController::class, 'laporanManager'])->name('laporan.manager');
    Route::get('/admin/laporan', [LaporanController::class, 'laporanManager'])->name('laporan.manager');

});
// Export PDF Laporan (Admin)
Route::middleware(['auth'])->prefix('admin')->group(function () {
    Route::get('/laporan/pdf', [LaporanController::class, 'exportPDF'])->name('laporan.pdf');
});