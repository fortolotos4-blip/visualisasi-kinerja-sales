@extends('layouts.app')
@include('layouts.navbar')

@section('title', 'Daftar Kunjungan Sales')

@section('content')
<div class="container mt-4">
    <h4>Daftar Kunjungan {{ Auth::user()->name }}</h4>

    <a href="{{ route('kunjungan.create') }}" class="btn btn-primary mb-3">+ Tambah Kunjungan</a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="GET" action="{{ route('kunjungan.index') }}" class="form-inline mb-3">
        <input type="date" name="tanggal" value="{{ request('tanggal') }}" class="form-control mr-2">

        <button type="submit" class="btn btn-primary">Filter</button>
    </form>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Customer</th>
                <th>Tujuan</th>
                <th>Hasil</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($kunjungan as $item)
                <tr>
                    <td>{{ ($kunjungan->currentPage() - 1) * $kunjungan->perPage() + $loop->iteration }}</td>
                    <td>{{ \Carbon\Carbon::parse($item->tanggal_kunjungan)->format('d-m-Y') }}</td>
                    <td>{{ $item->customer->nama_customer ?? '-' }}</td>
                    <td>{{ $item->tujuan }}</td>
                    <td>{{ $item->status }}</td>
                    <td>{{ $item->keterangan }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">Belum ada data kunjungan.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
        <!-- PAGINATION DIBAWAH TABEL -->
    <div class="d-flex justify-content-center">
        {{ $kunjungan->appends(request()->query())->links() }}
    </div>
</div>
@endsection
