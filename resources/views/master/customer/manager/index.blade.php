@extends('layouts.app')
@include('layouts.navbar')

@section('content')
<div class="container">
    <h4 class="mb-4">Data Customer</h4>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="GET" action="{{ route('customer.manager.index') }}" class="form-inline mb-3">

        <input type="text" name="search" placeholder="Cari nama customer..." value="{{ request('search') }}" class="form-control mr-2">

        <select name="sales_id" class="form-control mr-2">
            <option value="">-- Pilih Sales --</option>
            @foreach($sales as $s)
                <option value="{{ $s->id }}" {{ request('sales_id') == $s->id ? 'selected' : '' }}>
                    {{ $s->nama_sales }}
                </option>
            @endforeach
        </select>
        
        <button class="btn btn-primary">Filter</button>

    </form>
    <table class="table table-bordered table-striped">
        <thead>
    <tr>
        <th>No</th>
        <th>Sales</th>
        <th>Nama Customer</th>
        <th>Alamat</th>
        <th>No. HP</th>
        <th>Status</th>
    </tr>
    </thead>
        <tbody>
            @forelse($customers as $cr)
    <tr>
        <td>{{ $loop->iteration + ($customers->currentPage() - 1) * $customers->perPage() }}</td>
        <td>{{ $cr->user->name ?? '-' }}</td>
        <td>{{ $cr->nama_customer }}</td>
        <td>{{ $cr->alamat }}</td>
        <td>{{ $cr->telepon ?? '-' }}</td>
        <td>{{ ucfirst($cr->status_customer) }}</td>
    </tr>
    @empty
    <tr>
        <td colspan="7" class="text-center">Tidak ada data customer.</td>
    </tr>
    @endforelse
    </tbody>
    </table>
    <!-- PAGINATION DIBAWAH TABEL -->
    <div class="d-flex justify-content-center">
        {{ $customers->withQueryString()->links() }}
    </div>
</div>
@endsection