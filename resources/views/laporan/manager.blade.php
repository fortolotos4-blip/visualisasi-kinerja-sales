@extends('layouts.app')
@include('layouts.navbar')

@section('content')
<div class="container">
    <h4 class="mb-4">Laporan Penjualan Seluruh Sales</h4>

    <form method="GET" action="{{ route('laporan.manager') }}" class="row align-items-end g-3 mb-4">
    <div class="col-md-3">
        <label for="bulan">Bulan</label>
        <select name="bulan" class="form-control">
            <option value="">Semua</option>
            @for ($i = 1; $i <= 12; $i++)
                <option value="{{ $i }}" {{ request('bulan') == $i ? 'selected' : '' }}>
                    {{ \Carbon\Carbon::create()->month($i)->locale('id')->translatedFormat('F') }}
                </option>
            @endfor
        </select>
    </div>

    <div class="col-md-3">
        <label for="tahun">Tahun</label>
        <select name="tahun" class="form-control">
            <option value="">Semua</option>
            @for ($y = date('Y'); $y >= 2023; $y--)
                <option value="{{ $y }}" {{ request('tahun') == $y ? 'selected' : '' }}>{{ $y }}</option>
            @endfor
        </select>
    </div>

    <div class="col-md-3 d-flex gap-2">
        <button type="submit" class="btn btn-primary me-2">Filter</button>
        <a href="{{ route('laporan.pdf', ['bulan' => request('bulan'), 'tahun' => request('tahun')]) }}" class="btn btn-danger me-2" target="_blank">
            Cetak PDF
        </a>
    </div>
</form>

    <table class="table table-bordered table-striped">
        <thead class="thead-dark">
            <tr>
                <th>Nama Sales</th>
                <th>Kunjungan Berhasil</th>
                <th>Penawaran Berhasil</th>
                <th>Sales Order</th>
                <th>Total Penjualan</th>
                <th>Total Pembayaran</th>
                <th>Tagihan Belum Lunas</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $item)
                <tr>
                    <td>{{ $item['nama'] }}</td>
                    <td>{{ $item['jumlahKunjungan'] }}</td>
                    <td>{{ $item['jumlahPenawaran'] }}</td>
                    <td>{{ $item['jumlahSO'] }}</td>
                    <td>Rp{{ number_format($item['totalPenjualan'], 0, ',', '.') }}</td>
                    <td>Rp{{ number_format($item['totalPembayaran'], 0, ',', '.') }}</td>
                    <td 
                    @if ($item['piutang'] == 0)
                        class="text-secondary">Rp{{ number_format($item['piutang'], 0, ',', '.') }}</td> 
                    @else
                        class="text-danger">Rp{{ number_format($item['piutang'], 0, ',', '.') }}</td>   
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">Tidak ada data sales</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    @if(count($data) > 0)
    <table class="table table-bordered">
        <!-- table content -->
    </table>
@else
    <div class="alert alert-info">
        Tidak ada data penjualan pada bulan dan tahun yang dipilih.
    </div>
@endif
</div>
@endsection
