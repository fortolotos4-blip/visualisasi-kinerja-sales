@extends('layouts.app')
@include('layouts.navbar')

@section('content')
<div class="container mt-4">
    <h4>Dashboard Kinerja Sales - Bulan {{ $bulan }}/{{ $tahun }}</h4>

    {{-- Filter Bulan --}}
    <form method="GET" class="mb-3">
        <div class="row">
            <div class="col-md-3">
                <select name="bulan" class="form-control">
                    @foreach (range(1,12) as $b)
                        <option value="{{ str_pad($b, 2, '0', STR_PAD_LEFT) }}" {{ $bulan == str_pad($b,2,'0',STR_PAD_LEFT) ? 'selected' : '' }}>
                            {{ date('F', mktime(0, 0, 0, $b, 1)) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="tahun" class="form-control">
                    @for ($y = date('Y') - 3; $y <= date('Y'); $y++)
                        <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary">Tampilkan</button>
            </div>
        </div>
    </form>

    {{-- Ringkasan --}}
    <div class="row text-center mb-4">
        <div class="col-md-3"><div class="card"><div class="card-body">Kunjungan<br><strong>{{ $ringkasan['kunjungan'] }}</strong></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body">Penawaran<br><strong>{{ $ringkasan['penawaran'] }}</strong></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body">Sales Order<br><strong>{{ $ringkasan['so'] }}</strong></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body">Total Penjualan<br><strong>Rp {{ number_format($ringkasan['penjualan'], 0, ',', '.') }}</strong></div></div></div>
    </div>

    {{-- Tabel Pencapaian --}}
    <table class="table table-bordered">
        <thead class="table-secondary">
            <tr>
                <th>Nama Sales</th>
                <th>Target</th>
                <th>Realisasi</th>
                <th>Pencapaian (%)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data as $d)
                <tr>
                    <td>{{ $d['nama'] }}</td>
                    <td>Rp {{ number_format($d['target'], 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($d['penjualan'], 0, ',', '.') }}</td>
                    <td>
                        @if ($d['target'] == 0)
                            <span class="text-muted">Target belum diisi</span>
                        @else
                            <div class="progress">
                                <div class="progress-bar bg-{{ $d['pencapaian'] >= 100 ? 'success' : 'info' }}"
                                    role="progressbar"
                                    style="width: {{ $d['pencapaian'] }}%;"
                                    aria-valuenow="{{ $d['pencapaian'] }}"
                                    aria-valuemin="0"
                                    aria-valuemax="100">
                                    {{ $d['pencapaian'] }}%
                                </div>
                            </div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center">Tidak ada data</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- Chart Kunjungan / Penawaran / SO / Pembayaran --}}
    <div class="card">
        <div class="card-header bg-primary text-white"><h5 class="mb-0">Aktivitas Sales</h5></div>
        <div class="card-body">
            <div class="mt-5 row">
                <div class="col-md-6"><canvas id="chartKunjungan"></canvas></div>
                <div class="col-md-6"><canvas id="chartPenawaran"></canvas></div>
                <div class="col-md-6"><canvas id="chartSO"></canvas></div>
                <div class="col-md-6"><canvas id="chartPembayaran"></canvas></div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>

<script>

// Chart lain tetap bar
const salesNames = @json($kunjungan_per_sales->pluck('nama'));

new Chart(document.getElementById('chartKunjungan'), {
    type: 'bar',
    data: { labels: salesNames, datasets: [{ label: 'Kunjungan', data: @json($kunjungan_per_sales->pluck('jumlah')), backgroundColor: 'rgba(255, 99, 132, 0.6)' }] }
});
new Chart(document.getElementById('chartPenawaran'), {
    type: 'bar',
    data: { labels: salesNames, datasets: [{ label: 'Penawaran', data: @json($penawaran_per_sales->pluck('jumlah')), backgroundColor: 'rgba(255, 206, 86, 0.6)' }] }
});
new Chart(document.getElementById('chartSO'), {
    type: 'bar',
    data: { labels: salesNames, datasets: [{ label: 'Sales Order', data: @json($so_per_sales->pluck('jumlah')), backgroundColor: 'rgba(54, 162, 235, 0.6)' }] }
});
new Chart(document.getElementById('chartPembayaran'), {
    type: 'bar',
    data: { labels: salesNames, datasets: [{ label: 'Pembayaran', data: @json($pembayaran_per_sales->pluck('jumlah')), backgroundColor: 'rgba(75, 192, 192, 0.6)' }] }
});
</script>
@endsection
