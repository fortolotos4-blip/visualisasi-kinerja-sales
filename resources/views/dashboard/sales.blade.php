@extends('layouts.app')
@include('layouts.navbar')

@section('content')
<div class="container mt-4">
    {{-- Modal Peringatan --}}
    @if($isWarning && !empty($warningMessages))
    <div id="warningPopup" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-warning">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">Peringatan Kinerja</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <ul>
                        @foreach($warningMessages as $msg)
                            <li>{{ $msg }}</li>
                        @endforeach
                    </ul>
                    <p class="text-danger"><strong>Segera perbaiki kinerja Anda agar target tercapai!</strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <h4>Dashboard {{ Auth::user()->name }} - Bulan {{ $bulan }}/{{ $tahun }}</h4>

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
        <div class="col-md-3"><div class="card"><div class="card-body">Total Skor<br><strong>{{ number_format($total_skor, 2) }}</strong></div></div></div>
    </div>

    {{-- === Tabel Skor 12 Bulan (Hanya untuk Sales yang login) === --}}
    @php
        // ambil bobot/target dari $kontribusi (controller sudah mengirim)
        $bobot_penjualan = $kontribusi->bobot_penjualan ?? 0;
        $bobot_kunjungan = $kontribusi->bobot_kunjungan ?? 0;
        $bobot_penawaran = $kontribusi->bobot_penawaran ?? 0;
        $target_kunjungan = $kontribusi->target_kunjungan ?? 0;
        $target_penawaran = $kontribusi->target_penawaran ?? 0;

        // arrays bulanan dari controller (index 0 => Jan)
        $targets = $dataTargetBulanan ?? array_fill(0,12,0);
        $realisasi = $dataRealisasiBulanan ?? array_fill(0,12,0);
        $kunjunganB = $dataKunjunganBulanan ?? array_fill(0,12,0);
        $penawaranB = $dataPenawaranBulanan ?? array_fill(0,12,0);

        // bangun data monthly dengan skor
        $monthly = [];
        for ($i = 0; $i < 12; $i++) {
            $tgt = $targets[$i] ?? 0;
            $real = $realisasi[$i] ?? 0;
            $penc_penj = $tgt > 0 ? round(($real / $tgt) * 100, 2) : 0;
            $skor_penj = round($penc_penj * ($bobot_penjualan / 100), 2);

            $real_kunj = $kunjunganB[$i] ?? 0;
            $penc_kun = $target_kunjungan > 0 ? round(($real_kunj / $target_kunjungan) * 100, 2) : 0;
            $skor_kun = round($penc_kun * ($bobot_kunjungan / 100), 2);

            $real_penw = $penawaranB[$i] ?? 0;
            $penc_penw = $target_penawaran > 0 ? round(($real_penw / $target_penawaran) * 100, 2) : 0;
            $skor_penw = round($penc_penw * ($bobot_penawaran / 100), 2);

            $total_skor_bulan = round($skor_penj + $skor_kun + $skor_penw, 2);

            $monthly[$i+1] = [
                'target' => $tgt,
                'realisasi' => $real,
                'pencapaian_penjualan' => $penc_penj,
                'skor_penjualan' => $skor_penj,
                'realisasi_kunjungan' => $real_kunj,
                'pencapaian_kunjungan' => $penc_kun,
                'skor_kunjungan' => $skor_kun,
                'realisasi_penawaran' => $real_penw,
                'pencapaian_penawaran' => $penc_penw,
                'skor_penawaran' => $skor_penw,
                'total_skor' => $total_skor_bulan
            ];
        }

        // fungsi kelas sel berdasarkan skor
        if (!function_exists('cellClass')) {
            function cellClass($score) {
                if ($score === null) return '';
                if ($score == 0) return 'table-light text-muted'; // netral
                if ($score >= 80) return 'table-success text-success';
                if ($score < 50) return 'table-danger text-danger';
                return 'table-warning text-warning';
            }
        }

        // NOTE: untuk trend kita akan gunakan last-movement (last active month vs prev active month)
        // cari bulan terakhir yang punya "aktivitas bermakna" (penjualan/kunjungan/penawaran/total_skor > 0)
        $hasActivity = function($cell) {
            if ($cell === null) return false;
            $penjualan = $cell['realisasi'] ?? 0;
            $kunjungan = $cell['realisasi_kunjungan'] ?? 0;
            $penawaran = $cell['realisasi_penawaran'] ?? 0;
            $totalSkor = $cell['total_skor'] ?? 0;
            return ($penjualan > 0) || ($kunjungan > 0) || ($penawaran > 0) || ($totalSkor > 0);
        };

        $lastKey = null;
        for ($k = 12; $k >= 1; $k--) {
            if (isset($monthly[$k]) && $hasActivity($monthly[$k])) { $lastKey = $k; break; }
        }
        $prevKey = null;
        if ($lastKey !== null) {
            for ($k = $lastKey - 1; $k >= 1; $k--) {
                if (isset($monthly[$k]) && $hasActivity($monthly[$k])) { $prevKey = $k; break; }
            }
        }

        // build trendSummary untuk ditampilkan (last movement)
        $trendSummary = null;
        if ($lastKey !== null && $prevKey !== null) {
            $lastVal = $monthly[$lastKey]['total_skor'] ?? 0;
            $prevVal = $monthly[$prevKey]['total_skor'] ?? 0;
            if ($lastVal > $prevVal) $trendSummary = ['text'=>'Naik','class'=>'text-success','symbol'=>'▲','prev'=>$prevKey,'last'=>$lastKey];
            elseif ($lastVal < $prevVal) $trendSummary = ['text'=>'Turun','class'=>'text-danger','symbol'=>'▼','prev'=>$prevKey,'last'=>$lastKey];
            else $trendSummary = ['text'=>'Stabil','class'=>'text-secondary','symbol'=>'●','prev'=>$prevKey,'last'=>$lastKey];
        } elseif ($lastKey !== null && $prevKey === null) {
            $trendSummary = ['text'=>'Stabil','class'=>'text-secondary','symbol'=>'●','prev'=>'-','last'=>$lastKey];
        } else {
            $trendSummary = null;
        }
    @endphp

    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Tabel Skor Kinerja — 12 Bulan ({{ $tahun }})</h5>
        </div>
        <div class="card-body p-2">
            <div class="table-responsive">
                <table class="table table-bordered text-center align-middle table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            @foreach(['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'] as $m)
                                <th>{{ $m }}</th>
                            @endforeach
                            <th>Trend</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            @for($m=1;$m<=12;$m++)
                            @php
                                $cell = $monthly[$m] ?? null;
                                $score = $cell['total_skor'] ?? 0;

                                // previous month's score presence for small per-cell arrow (month-to-month)
                                $prevScore = ($m > 1 && isset($monthly[$m-1]['total_skor'])) ? $monthly[$m-1]['total_skor'] : null;
                                $trend = null;
                                if ($prevScore !== null) {
                                    if ($score > $prevScore) $trend = 'up';
                                    elseif ($score < $prevScore) {
                                        if ($score == 0) $trend = 'same';
                                        else $trend = 'down';
                                    } else $trend = 'same';
                                }

                                $tdClass = cellClass($score);
                            @endphp

                            <td class="{{ $tdClass }}">
                                <div><strong>{{ number_format($score, 2) }}</strong></div>
                                <div style="font-size:0.8em;">
                                    @if($trend === 'up')
                                        <span class="text-success">▲</span>
                                    @elseif($trend === 'down')
                                        <span class="text-danger">▼</span>
                                    @elseif($trend === 'same')
                                        <span class="text-secondary">●</span>
                                    @endif
                                </div>
                            </td>
                        @endfor

                            {{-- ringkasan trend berdasarkan last movement --}}
                            <td>
                                @if($trendSummary)
                                    <span class="{{ $trendSummary['class'] }}">{{ $trendSummary['text'] }} {{ $trendSummary['symbol'] }}</span>
                                    <div style="font-size:0.8em;">({{ $trendSummary['prev'] }} → {{ $trendSummary['last'] }})</div>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-2">
                <small>
                    <span class="badge badge-success">>= 80 (Tinggi)</span>
                    <span class="badge badge-warning">50 - 79.99 (Sedang)</span>
                    <span class="badge badge-danger">&lt; 50 (Rendah)</span>
                    &nbsp;&nbsp; Panah: ▲ naik, ▼ turun, ● stabil
                </small>
            </div>
        </div>
    </div>

    {{-- Tabel Pencapaian 1 --}}
    <table class="table table-bordered">
        <thead class="table-secondary">
            <tr>
                <th>Parameter</th>
                <th>Bobot (%)</th>
                <th>Target</th>
                <th>Realisasi</th>
                <th>Pencapaian (%)</th>
                <th>Skor</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data as $d)
                <tr>
                    <td>Penjualan</td>
                    <td>{{ $d['bobot_penjualan']}}%</td>
                    <td>Rp {{ number_format($d['target'], 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($d['penjualan'], 0, ',', '.') }}</td>
                    <td>
                        @if($d['target'] > 0)
                            <div class="progress">
                                <div class="progress-bar" role="progressbar" style="width: {{ $d['pencapaian'] }}%;" aria-valuenow="{{ $d['pencapaian'] }}" aria-valuemin="0" aria-valuemax="100">
                                    {{ $d['pencapaian'] }}%
                                </div>
                            </div>
                        @else
                            <span class="text-muted">Target belum diisi</span>
                        @endif
                    </td>
                    <td>{{ $d['skor_penjualan']}}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center">Tidak ada data</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- Chart --}}
    <div class="card">
        <div class="card-header bg-primary text-white"><h5 class="mb-0">Target Vs Realisasi Penjualan ({{ $tahun }})</h5></div>
        <div class="card-body">
            <canvas id="salesChart"></canvas>
        </div>
    </div>

    <br>

    {{-- Tabel Pencapaian 2 --}} 
<table class="table table-bordered">
    <thead class="table-secondary">
        <tr>
            <th>Parameter</th>
            <th>Bobot (%)</th>
            <th>Target</th>
            <th>Realisasi</th>
            <th>Pencapaian (%)</th>
            <th>Skor</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($data2 as $d)
            <tr>
                <td>Kunjungan</td>
                <td>{{ $d['bobot_kunjungan'] }}%</td>
                <td>{{ $d['target_kunjungan'] }}</td>
                <td>{{ $d['realisasi_kunjungan'] }}</td>
                <td>
                    <div class="progress">
                        <div class="progress-bar" role="progressbar" 
                             style="width: {{ $d['pencapaian_kunjungan'] }}%;" 
                             aria-valuenow="{{ $d['pencapaian_kunjungan'] }}" aria-valuemin="0" aria-valuemax="100">
                            {{ $d['pencapaian_kunjungan'] }}%
                        </div>
                    </div>
                </td>
                <td>{{ $d['skor_kunjungan'] }}</td>
            </tr>
            <tr>
                <td>Penawaran</td>
                <td>{{ $d['bobot_penawaran'] }}%</td>
                <td>{{ $d['target_penawaran'] }}</td>
                <td>{{ $d['realisasi_penawaran'] }}</td>
                <td>
                    <div class="progress">
                        <div class="progress-bar bg-success" role="progressbar" 
                             style="width: {{ $d['pencapaian_penawaran'] }}%;" 
                             aria-valuenow="{{ $d['pencapaian_penawaran'] }}" aria-valuemin="0" aria-valuemax="100">
                            {{ $d['pencapaian_penawaran'] }}%
                        </div>
                    </div>
                </td>
                <td>{{ $d['skor_penawaran'] }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center">Tidak ada data kontribusi parameter</td></tr>
        @endforelse
    </tbody>
</table>


    <br>

    {{-- Chart Konversi Aktivitas --}}

    <div class="card">
        <div class="card-header bg-primary text-white"><h5 class="mb-0">Grafik Aktivitas ({{ $tahun }})</h5></div>
        <div class="card-body">
            <canvas id="conversionChart"></canvas>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

{{-- Modal Warning sekali per hari --}}
@if($isWarning && !empty($warningMessages))
<script>
document.addEventListener("DOMContentLoaded", function() {
    const todayKey = 'salesWarningShown_' + new Date().toISOString().slice(0,10);
    if (!sessionStorage.getItem(todayKey)) {
        $('#warningPopup').modal('show');
        sessionStorage.setItem(todayKey, 'true');
    }
});
</script>
@endif

{{-- Grafik Penjualan vs Target --}}
<script>
const ctx = document.getElementById('salesChart').getContext('2d');

new Chart(ctx, {
    type: 'line',
    data: {
        labels: @json($bulanLabels), // label bulan dari controller
        datasets: [
            {
                label: 'Target Penjualan',
                data: @json($dataTargetBulanan),
                borderColor: 'rgba(54, 162, 235, 1)',
                backgroundColor: 'rgba(54, 162, 235, 0.1)',
                tension: 0.3,
                fill: false,
                pointBackgroundColor: 'rgba(54, 162, 235, 1)'
            },
            {
                label: 'Realisasi Penjualan',
                data: @json($dataRealisasiBulanan),
                borderColor: 'rgba(75, 192, 192, 1)',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                tension: 0.3,
                fill: false,
                pointBackgroundColor: 'rgba(75, 192, 192, 1)'
            }
        ]
    },
    options: {
        responsive: true,
        interaction: {
            mode: 'index',
            intersect: false
        },
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        // tampilkan tooltip hanya untuk realisasi
                        if (context.dataset.label === 'Realisasi Penjualan') {
                            let value = Number(context.raw);
                            let target = Number(context.chart.data.datasets[0].data[context.dataIndex]);
                            let diff = target > 0 ? ((value - target) / target) * 100 : 0;
                            let symbol = diff > 0 ? '▲' : (diff < 0 ? '▼' : '●');

                            // Format angka rupiah
                            let valFormatted = value.toLocaleString('id-ID');

                            return `Realisasi: Rp ${valFormatted} (${symbol} ${diff.toFixed(1)}%)`;
                        }
                        return null;
                    }
                },
                filter: function(item) {
                    // hanya tampilkan tooltip untuk realisasi
                    return item.dataset.label === 'Realisasi Penjualan';
                }
            },
            legend: {
                labels: {
                    filter: function(item) {
                        // tetap tampilkan kedua legend
                        return true;
                    }
                }
            },
            title: {
                display: true,
                text: 'Perbandingan Target vs Realisasi Penjualan (Bulanan)',
                font: { size: 16 }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return 'Rp ' + value.toLocaleString('id-ID');
                    }
                }
            }
        }
    }
});

// Grafik Konversi Aktivitas
const conversionCtx = document.getElementById('conversionChart').getContext('2d');
new Chart(conversionCtx, {
    type: 'line',
    data: {
        labels: @json($bulanLabels),
        datasets: [
            {
                label: 'Kunjungan',
                data: @json($dataKunjunganBulanan),
                borderColor: '#36A2EB',
                backgroundColor: 'rgba(54,162,235,0.2)',
                tension: 0.3,
                fill: false
            },
            {
                label: 'Penawaran',
                data: @json($dataPenawaranBulanan),
                borderColor: '#FFCE56',
                backgroundColor: 'rgba(255,206,86,0.2)',
                tension: 0.3,
                fill: false
            },
            {
                label: 'Sales Order',
                data: @json($dataSOBulanan),
                borderColor: '#FF6384',
                backgroundColor: 'rgba(255,99,132,0.2)',
                tension: 0.3,
                fill: false
            }
        ]
    },
    options: {
        responsive: true,
        scales: {
            y: { beginAtZero: true }
        }
    }
});
</script>
@endsection
