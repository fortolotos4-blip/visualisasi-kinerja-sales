@extends('layouts.app')
@include('layouts.navbar')

@section('content')
<div class="container">
    <h4>Daftar Sales Order</h4>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="GET" action="{{ route('sales-order.index') }}" class="mb-3">
    <div class="form-row">
        <div class="col">
            <input type="date" name="tanggal" class="form-control" value="{{ request('tanggal') }}">
        </div>
        <div class="col">
            <select name="status" class="form-control">
                <option value="">-- Status --</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="diproses" {{ request('status') == 'diproses' ? 'selected' : '' }}>Diproses</option>
                <option value="selesai" {{ request('status') == 'selesai' ? 'selected' : '' }}>Selesai</option>
                <option value="ditolak" {{ request('status') == 'ditolak' ? 'selected' : '' }}>Ditolak</option>
            </select>
        </div>
        <div class="col">
            <select name="sales_id" class="form-control">
                <option value="">-- Sales --</option>
                @foreach ($allSales as $s)
                    <option value="{{ $s->id }}" {{ request('sales_id') == $s->id ? 'selected' : '' }}>
                        {{ $s->nama_sales }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col">
            <select name="customer_id" class="form-control">
                <option value="">-- Customer --</option>
                @foreach ($allCustomers as $c)
                    <option value="{{ $c->id }}" {{ request('customer_id') == $c->id ? 'selected' : '' }}>
                        {{ $c->nama_customer }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col">
            <button type="submit" class="btn btn-primary">Filter</button>
        </div>
    </div>
</form>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>No</th>
                <th>Nomor SO</th>
                <th>Tanggal</th>
                <th>Sales</th>
                <th>Customer</th>
                <th>Produk</th>
                <th>Jumlah</th>
                <th>Harga</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($orders as $order)
            <tr>
                <td>{{ ($orders->currentPage() - 1) * $orders->perPage() + $loop->iteration }}</td>
                <td>{{ $order->nomor_so }}</td>
                <td>{{ $order->tanggal }}</td>
                <td>{{ optional($order->sales)->nama_sales ?? '-' }}</td>
                <td>{{ $order->customer->nama_customer ?? '-' }}</td>

                <td>
                    @if(isset($order->details) && $order->details->count())
                        <ul class="mb-0 pl-3">
                            @foreach($order->details as $d)
                                <li>{{ $d->product_name ?? '-' }} &times; {{ $d->qty }}</li>
                            @endforeach
                        </ul>
                    @else
                        {{ optional($order->produk)->nama_produk ?? '-' }}
                    @endif
                </td>

                <td>
                    {{ isset($order->details) && $order->details->count() 
                        ? $order->details->sum('qty') 
                        : ($order->jumlah ?? '-') }}
                </td>

                <td>
                    Rp {{ number_format($order->total_harga ?? ($order->harga_satuan * $order->jumlah ?? 0), 0, ',', '.') }}
                </td>

                <td>{{ ucfirst($order->status) }}</td>

                {{-- 🔹 Kolom Aksi --}}
                <td>
                    <a href="{{ route('sales-order.print', $order->id) }}"
                    class="btn btn-sm btn-outline-secondary"
                    target="_blank">
                        Cetak
                    </a>
                </td>
            </tr>
        @empty
                <tr>
                    <td colspan="10" class="text-center">Belum ada data sales order.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <!-- PAGINATION DIBAWAH TABEL -->
    <div class="d-flex justify-content-center">
        {{ $orders->withQueryString()->links() }}
    </div>
</div>
@endsection
