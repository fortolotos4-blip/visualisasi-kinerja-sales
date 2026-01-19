@extends('layouts.app')
@include('layouts.navbar')
@section('title', 'Daftar Penjualan')

@section('content')
<div class="container mt-4">
    <h4>Daftar Penjualan</h4>
    
    <form method="GET" action="{{ route('penjualan.manager') }}" class="form-inline mb-3">
        <input type="date" name="tanggal" value="{{ request('tanggal') }}" class="form-control mr-2">

        <select name="sales_id" class="form-control mr-2">
            <option value="">-- Pilih Sales --</option>
            @foreach($sales as $s)
                <option value="{{ $s->id }}" {{ request('sales_id') == $s->id ? 'selected' : '' }}>
                    {{ $s->nama_sales }}
                </option>
            @endforeach
        </select>

        <select name="customer_id" class="form-control mr-2">
            <option value="">-- Pilih Customer --</option>
            @foreach($customers as $c)
                <option value="{{ $c->id }}" {{ request('customer_id') == $c->id ? 'selected' : '' }}>
                    {{ $c->nama_customer }}
                </option>
            @endforeach
        </select>

        <button type="submit" class="btn btn-primary">Filter</button>
    </form>
    <table class="table table-bordered table-striped">
        <thead>
    <tr>
        <th>No</th>
        <th>Faktur</th>
        <th>Sales Order</th>
        <th>Tanggal Pelunasan</th>
        <th>Sales</th>
        <th>Customer</th>
        <th>Total</th>
    </tr>
</thead>

        <tbody>
@foreach($penjualan as $p)
    <tr>
        <td>{{ ($penjualan->currentPage() - 1) * $penjualan->perPage() + $loop->iteration }}</td>
        <td>{{ $p->nomor_faktur }}</td>
        <td>{{ $p->salesOrder->nomor_so }}</td>
        <td>{{ $p->tanggal_pelunasan }}</td>
        <td>{{ $p->sales->nama_sales ?? '-' }}</td>
        <td>{{ $p->customer->nama_customer ?? '-' }}</td>
        <td>Rp {{ number_format($p->total_harga) }}</td>
    </tr>
@endforeach
</tbody>
    </table>
    <!-- PAGINATION DIBAWAH TABEL -->
    <div class="d-flex justify-content-center">
        {{ $penjualan->withQueryString()->links() }}
    </div>
</div>

@endsection
