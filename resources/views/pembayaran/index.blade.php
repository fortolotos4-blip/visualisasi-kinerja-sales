@php use Illuminate\Support\Str; @endphp
@extends('layouts.app')
@include('layouts.navbar')

@section('content')
<div class="container">
    <h4>Daftar Pembayaran {{ Auth::user()->name }}</h4>

    <a href="{{ route('pembayaran.create') }}" class="btn btn-primary mb-3">+ Tambah Pembayaran</a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="GET" action="{{ route('pembayaran.index') }}" class="form-inline mb-3">
            <input type="date" name="tanggal" class="form-control mr-2" value="{{ request('tanggal') }}">
            <button type="submit" class="btn btn-primary">Filter</button>
</form>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>No</th>
                <th>Nomor SO</th>
                <th>Tanggal Pembayaran</th>
                <th>Jumlah</th>
                <th>Bukti</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($pembayaran as $item)
                <tr>
                    <td>{{ ($pembayaran->currentPage() - 1) * $pembayaran->perPage() + $loop->iteration }}</td>
                    <td>{{ $item->salesOrder->nomor_so ?? '-' }}</td>
                    <td>{{ $item->tanggal_pembayaran }}</td>
                    <td>Rp{{ number_format($item->jumlah, 0, ',', '.') }}</td>
                    <td>
                    @if($item->bukti)
                    @if(Str::startsWith($item->bukti, 'http'))
                        <a href="{{ $item->bukti }}" target="_blank">Lihat Bukti</a>
                    @else
                        <span class="text-muted">Bukti lama (tidak tersedia)</span>
                    @endif
                @else
                    -
                @endif

                    </td>

                    <td>{{ ucfirst($item->status) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center">Belum ada data pembayaran.</td></tr>
            @endforelse
        </tbody>
    </table>
    <!-- PAGINATION DIBAWAH TABEL -->
    <div class="d-flex justify-content-center">
        {{ $pembayaran->withQueryString()->links() }}
    </div>
</div>
@endsection
