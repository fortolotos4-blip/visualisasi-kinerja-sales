@extends('layouts.app')
@include('layouts.navbar')

@section('content')
<div class="container">
    <h4 class="mb-4">Laporan Rekap Kinerja Sales</h4>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered table-striped">
        <tbody>
            <tr>
                <th>Jumlah Kunjungan</th>
                <td>{{ $jumlahKunjungan }}</td>
            </tr>
            <tr>
                <th>Jumlah Penawaran</th>
                <td>{{ $jumlahPenawaran }}</td>
            </tr>
            <tr>
            </tr>
            <tr>
                <th>Jumlah Sales Order</th>
                <td>{{ $jumlahSO }}</td>
            </tr>
            <tr>
                <th>Total Penjualan</th>
                <td>Rp{{ number_format($totalPenjualan, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <th>Total Pembayaran</th>
                <td>Rp{{ number_format($totalPembayaran, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <th>Tagihan Belum Lunas</th>
                <td class="text-danger">Rp{{ number_format($piutang, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>
</div>
@endsection
