@extends('layouts.app')
@include('layouts.navbar')

@section('title', 'Sales Order Saya')

@section('content')
<div class="container mt-4">
    <h4>Sales Order {{ Auth::user()->name }}</h4>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <a href="{{ route('sales-order.create') }}" class="btn btn-primary mb-3">+ Buat Sales Order</a>

    <form method="GET" action="{{ route('sales-order.my') }}" class="mb-3">
    <div class="form-row">
        <div class="col">
            <input type="date" name="tanggal" class="form-control" value="{{ request('tanggal') }}">
        </div>
        <div class="col">
            <select name="status" class="form-control">
                <option value="">-- Status --</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="diproses" {{ request('status') == 'diproses' ? 'selected' : '' }}>Diproses</option>
                <option value="lunas" {{ request('status') == 'lunas' ? 'selected' : '' }}>Lunas</option>
            </select>
        </div>
        <div class="col">
            <select name="customer_id" class="form-control">
                <option value="">-- Customer --</option>
                @foreach ($customers as $c)
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
                <th>Customer</th>
                <th>Produk</th>
                <th>Jumlah</th>
                <th>Harga</th>
                <th>Tanggal kirim</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
                <tr>
                   <td>{{ ($orders->currentPage() - 1) * $orders->perPage() + $loop->iteration }}</td>
                    <td>{{ $order->nomor_so }}</td>
                    <td>{{ $order->tanggal }}</td>
                    <td>{{ $order->customer->nama_customer ?? '-' }}</td>

                    {{-- Produk: tampilkan list nama x qty kalau ada details --}}
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

                    {{-- Jumlah = total qty dari details (fallback ke header jumlah lama) --}}
                    <td>{{ isset($order->details) && $order->details->count() ? $order->details->sum('qty') : ($order->jumlah ?? '-') }}</td>

                    {{-- Harga = total_harga di header (lebih akurat untuk multi-item) --}}
                    <td>Rp {{ number_format($order->total_harga ?? 0, 0, ',', '.') }}</td>

                    <td>{{ $order->tanggal_pengiriman ?? '-' }}</td>
                    <td>{{ ucfirst($order->status) }}</td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center">Belum ada Sales Order.</td></tr>
            @endforelse
        </tbody>
    </table>
    <!-- PAGINATION DIBAWAH TABEL -->
    <div class="d-flex justify-content-center">
        {{ $orders->withQueryString()->links() }}
    </div>
</div>
@endsection
