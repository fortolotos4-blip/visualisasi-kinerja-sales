@php use Illuminate\Support\Str; @endphp
@extends('layouts.app')
@include('layouts.navbar')

@section('content')
<div class="container">
    <h4>Daftar Pembayaran</h4>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="GET" action="{{ route('pembayaran.index') }}" class="form-inline mb-3">
        <input type="date" name="tanggal" value="{{ request('tanggal') }}" class="form-control mr-2">

        <button type="submit" class="btn btn-primary">Filter</button>
    </form>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>No</th>
                <th>No SO</th>
                <th>Sales</th>
                <th>Customer</th>
                <th>Jumlah</th>
                <th>Tanggal Bayar</th>
                <th>Bukti</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($pembayaran as $row)
                <tr>
                    <td>{{ ($pembayaran->currentPage() - 1) * $pembayaran->perPage() + $loop->iteration }}</td>
                    <td>{{ $row->salesOrder->nomor_so }}</td>
                    <td>{{ optional($row->salesOrder->sales->user)->name ?? '-' }}</td>
                    <td>{{ $row->salesOrder->customer->nama_customer }}</td>
                    <td>Rp{{ number_format($row->jumlah, 0, ',', '.') }}</td>
                    <td>{{ $row->tanggal_pembayaran }}</td>
                    <td>
                        @if($row->bukti && Str::startsWith($row->bukti, 'http'))
                            <a href="{{ $row->bukti }}" target="_blank" rel="noopener">
                                Lihat Bukti
                            </a>
                        @else
                            <span class="text-muted">Tidak ada</span>
                        @endif
                    </td>

                    <td>{{ ucfirst($row->status) }}</td>
                    <td>@if($row->status !== 'diterima')
                        <a href="{{ route('pembayaran.admin.edit', ['id' => $row->id, 'page' => $pembayaran->currentPage()]) }}" class="btn btn-sm btn-primary">Verifikasi</a>
                    @else
                        <span class="text-muted">Selesai</span>
                    @endif
                </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">Tidak ada data pembayaran</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <!-- PAGINATION DIBAWAH TABEL -->
    <div class="d-flex justify-content-center">
        {{ $pembayaran->withQueryString()->links() }}
    </div>
</div>
@endsection
