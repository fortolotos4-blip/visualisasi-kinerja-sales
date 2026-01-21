@extends('layouts.app')
@include('layouts.navbar')

@section('content')
<div class="container mt-4">

    {{-- Pop-up Warning Kontribusi Parameter --}}
    @if(!$kontribusi)
    <div class="modal fade" id="warningModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-warning">
                <div class="modal-header bg-warning text-dark py-2">
                    <h6 class="modal-title">Informasi</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body small">
                    Bobot penilaian bulan ini belum diatur.  
                    Skor kinerja mungkin belum akurat.
                </div>
                <div class="modal-footer py-2">
                    <a href="{{ route('kontribusi_parameters.index') }}" class="btn btn-sm btn-primary">
                        Atur
                    </a>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
    @if(!$kontribusi)
    <script>
    document.addEventListener("DOMContentLoaded", function () {

        const key = 'manager_warning_kontribusi';
        const today = new Date().toISOString().slice(0,10);
        const lastShown = localStorage.getItem(key);

        if (lastShown !== today) {
            const modal = new bootstrap.Modal(document.getElementById('warningModal'));
            modal.show();
            localStorage.setItem(key, today);
        }

    });
    </script>
    @endif

    <h4>Dashboard Kinerja Sales - Bulan {{ date('F', mktime(0,0,0,$bulan,1)) }} {{ $tahun }}</h4>

    {{-- Filter Bulan & Tahun --}}
    <form method="GET" class="mb-3">
        <div class="row">
            <div class="col-md-3">
                <select name="bulan" class="form-control">
                    @foreach (range(1,12) as $b)
                        <option value="{{ $b }}" {{ $bulan == $b ? 'selected' : '' }}>
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

    {{-- === Tabel Skor Kinerja Sales (ringkas bulan aktif) === --}}
    <h5 class="mt-4 mb-3">Tabel Skor Kinerja Sales</h5>
    <table class="table table-bordered text-center align-middle">
        <thead class="table-secondary">
            <tr>
                <th>Nama Sales</th>
                <th>Target Penjualan</th>
                <th>Total Penjualan</th>
                <th>Skor Penjualan</th>
                <th>Skor Kunjungan</th>
                <th>Skor Penawaran</th>
                <th><strong>Total Skor</strong></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data as $d)
                <tr>
                    <td>{{ $d['nama'] }}</td>
                    <td>Rp {{ number_format($d['target_penjualan'], 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($d['penjualan'], 0, ',', '.') }}</td>
                    <td>{{ $d['skor_penjualan'] }}</td>
                    <td>{{ $d['skor_kunjungan'] }}</td>
                    <td>{{ $d['skor_penawaran'] }}</td>
                    <td class="fw-bold 
                        {{ $d['total_skor'] >= 80 ? 'text-success' : ($d['total_skor'] < 50 ? 'text-danger' : 'text-warning') }}">
                        {{ $d['total_skor'] }}
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center">Tidak ada data</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- === Tabel 12 Bulan Per Sales === --}}
    <h5 class="mt-4 mb-3">Tabel Skor Kinerja Sales — 12 Bulan ({{ $tahun }})</h5>
    <table class="table table-bordered text-center align-middle small">
        <thead class="table-secondary">
            <tr>
                <th rowspan="2">Nama Sales</th>
                <th colspan="12">Bulan</th>
                <th rowspan="2">Trend</th>
            </tr>
            <tr>
                @foreach (['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'] as $m)
                    <th>{{ $m }}</th>
                @endforeach
            </tr>
        </thead>

        <tbody>
            @forelse ($data as $d)
                <tr>
                    <td class="text-start">{{ $d['nama'] }}</td>

                    {{-- LOOP 12 BULAN --}}
                    @for ($m = 1; $m <= 12; $m++)
                        @php
                            $cell  = $d['monthly'][$m] ?? null;
                            $score = $cell['total_skor'] ?? 0;

                            // warna sel
                            if ($score == 0) {
                                $cellClass = 'table-light text-muted';
                            } elseif ($score >= 80) {
                                $cellClass = 'table-success text-success';
                            } elseif ($score < 50) {
                                $cellClass = 'table-danger text-danger';
                            } else {
                                $cellClass = 'table-warning text-warning';
                            }

                            $scoreDisplay = number_format($score, 2);
                        @endphp

                        <td class="{{ $cellClass }}">
                            <div><strong>{{ $scoreDisplay }}</strong></div>
                            <div style="font-size:0.8em;">
                                @php
                                    if ($m > 1 && isset($d['monthly'][$m-1])) {
                                        $prev = $d['monthly'][$m-1]['total_skor'] ?? 0;

                                        if ($score > $prev)       echo '<span class="text-success">▲</span>';
                                        elseif ($score < $prev)   echo '<span class="text-danger">▼</span>';
                                        else                      echo '<span class="text-secondary">●</span>';
                                    }
                                @endphp
                            </div>
                        </td>
                    @endfor

                    {{-- TREND 2 BULAN TERAKHIR YANG ADA NILAI --}}
                    @php
                        $activeMonths = [];
                        for ($i = 1; $i <= 12; $i++) {
                            $c = $d['monthly'][$i] ?? null;
                            if ($c && ($c['total_skor'] ?? 0) > 0) {
                                $activeMonths[] = $i;
                            }
                        }

                        $summary = null;
                        if (count($activeMonths) >= 2) {
                            $lastKey  = $activeMonths[count($activeMonths) - 1];
                            $prevKey  = $activeMonths[count($activeMonths) - 2];
                            $lastScore = $d['monthly'][$lastKey]['total_skor'];
                            $prevScore = $d['monthly'][$prevKey]['total_skor'];

                            if      ($lastScore > $prevScore) $summary = 'Naik';
                            elseif  ($lastScore < $prevScore) $summary = 'Turun';
                            else                              $summary = 'Stabil';
                        } elseif (count($activeMonths) == 1) {
                            $summary = 'Stabil';
                        }
                    @endphp

                    <td>
                        @if($summary === 'Naik')
                            <span class="text-success">Naik ▲</span>
                        @elseif($summary === 'Turun')
                            <span class="text-danger">Turun ▼</span>
                        @elseif($summary === 'Stabil')
                            <span class="text-secondary">Stabil ●</span>
                        @else
                            -
                        @endif
                    </td>

                </tr>
            @empty
                <tr><td colspan="14" class="text-center">Tidak ada data</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- Legenda --}}
    <div class="mt-2">
        <small>
            <span class="badge bg-success">&gt;= 80 (Tinggi)</span>
            <span class="badge bg-warning text-dark">50 - 79.99 (Sedang)</span>
            <span class="badge bg-danger">&lt; 50 (Rendah)</span>
            &nbsp; &nbsp; Panah: ▲ naik, ▼ turun, ● stabil
        </small>
    </div>

    {{-- === GRAFIK SKOR KINERJA SALES 12 BULAN (PER SALES) === --}}
<div class="card mt-4">
    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Grafik Skor Kinerja Sales — 12 Bulan ({{ $tahun }})</h5>
        <div class="d-flex align-items-center">
            <span class="me-2">Pilih Sales:</span>
            <select id="selectSalesTrend" class="form-select form-select-sm">
                @foreach($data as $idx => $d)
                    <option value="{{ $idx }}">{{ $d['nama'] }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="card-body">
        <canvas id="chartSkorSales" height="120"></canvas>
        <small class="text-muted">
            Tooltip menampilkan skor dan perubahan terhadap bulan sebelumnya (▲ naik, ▼ turun, ● stabil).
        </small>
    </div>
</div>


    {{-- === Tabel Min dan Max === --}}
    <div class="card mt-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">Nilai Tertinggi dan Terendah</h5>
        </div>
        <div class="card-body">
            <table class="table table-bordered text-center align-middle">
                <thead class="table-secondary">
                    <tr>
                        <th>Tipe</th>
                        <th>Nama Sales</th>
                        <th>Kunjungan</th>
                        <th>Penawaran</th>
                        <th>Total Penjualan (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="table-success">
                        <td><strong>MAX</strong></td>
                        <td>{{ $maxData['nama'] }}</td>
                        <td>{{ $maxData['kunjungan'] }}</td>
                        <td>{{ $maxData['penawaran'] }}</td>
                        <td>Rp {{ number_format($maxData['penjualan'], 0, ',', '.') }}</td>
                    </tr>
                    <tr class="table-danger">
                        <td><strong>MIN</strong></td>
                        <td>{{ $minData['nama'] }}</td>
                        <td>{{ $minData['kunjungan'] }}</td>
                        <td>{{ $minData['penawaran'] }}</td>
                        <td>Rp {{ number_format($minData['penjualan'], 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Chart Target vs Realisasi Penjualan --}}
    <div class="card mt-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Target Vs Realisasi Penjualan ({{ $tahun }})</h5>
        </div>
        <div class="card-body">
            <canvas id="salesChart"></canvas>
        </div>
    </div>

    {{-- Chart Kunjungan & Penawaran --}}
    <div class="card mt-4 mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Kunjungan & Penawaran ({{ $tahun }})</h5>
        </div>
        <div class="card-body">
            <canvas id="chartKunjunganPenawaran" height="100"></canvas>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>

<script>
/* Chart Target vs Realisasi Penjualan */
const ctx = document.getElementById('salesChart').getContext('2d');
const salesChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'],
        datasets: [
            {
                label: 'Target Penjualan',
                data: @json($grafik['target']),
                borderColor: 'rgba(255, 162, 235, 1)',
                borderWidth: 2,
                fill: false,
                tension: 0.3,
                pointBackgroundColor: 'rgba(255, 99, 132, 1)'
            },
            {
                label: 'Total Penjualan',
                data: @json($grafik['penjualan']),
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 2,
                fill: false,
                tension: 0.3,
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
                        if (context.dataset.label === 'Total Penjualan') {
                            let value = Number(context.raw);
                            let target = Number(context.chart.data.datasets[0].data[context.dataIndex]);
                            let diff = ((value - target) / (target || 1)) * 100;
                            let symbol = diff > 0 ? '▲' : (diff < 0 ? '▼' : '●');
                            let valFormatted = value.toLocaleString('id-ID');
                            return `Total Penjualan: Rp ${valFormatted} (${symbol} ${diff.toFixed(1)}%)`;
                        }
                        return null;
                    }
                },
                filter: function(item) {
                    return item.dataset.label === 'Total Penjualan';
                }
            },
            legend: {
                labels: {
                    filter: function(item) {
                        return item.text !== 'Target Penjualan';
                    }
                }
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
</script>

<script>
/* Chart Kunjungan & Penawaran */
const ctx2 = document.getElementById('chartKunjunganPenawaran').getContext('2d');
const chartKunjunganPenawaran = new Chart(ctx2, {
    type: 'line',
    data: {
        labels: ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'],
        datasets: [
            {
                label: 'Total Kunjungan',
                data: @json($grafikKunjungan),
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 2,
                fill: false,
                tension: 0.3,
                pointBackgroundColor: 'rgba(54, 162, 235, 1)'
            },
            {
                label: 'Total Penawaran',
                data: @json($grafikPenawaran),
                borderColor: 'rgba(255, 99, 132, 1)',
                borderWidth: 2,
                fill: false,
                tension: 0.3,
                pointBackgroundColor: 'rgba(255, 99, 132, 1)'
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return `${context.dataset.label}: ${context.raw} kali`;
                    }
                }
            }
        },
        scales: {
            y: { beginAtZero: true, title: { display: true, text: 'Jumlah Aktivitas' } },
            x: { title: { display: true, text: 'Bulan' } }
        }
    }
});
</script>

<script>
/* Grafik Skor Kinerja Sales 12 Bulan (per sales) */
document.addEventListener('DOMContentLoaded', function () {

    // Susun data skor per sales dari PHP ke JS
    const skorSales = [
        @foreach($data as $d)
        {
            name: "{{ $d['nama'] }}",
            scores: [
                @for($m = 1; $m <= 12; $m++)
                    {{ $d['monthly'][$m]['total_skor'] ?? 0 }}{{ $m < 12 ? ',' : '' }}
                @endfor
            ]
        }{{ !$loop->last ? ',' : '' }}
        @endforeach
    ];

    const bulanLabels = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    const warnaLine   = 'rgba(75, 192, 192, 1)';

    const selectSalesTrend = document.getElementById('selectSalesTrend');
    const ctxSkor          = document.getElementById('chartSkorSales').getContext('2d');

    // index default sales yang dipilih
    let currentIndex = parseInt(selectSalesTrend.value) || 0;

    function makeDataset(index) {
        const s = skorSales[index];
        return {
            label: s.name,
            data: s.scores,
            borderColor: warnaLine,
            backgroundColor: 'transparent',
            borderWidth: 2,
            tension: 0.3,
            pointRadius: 4,
            pointHoverRadius: 6
        };
    }

    const trendChart = new Chart(ctxSkor, {
        type: 'line',
        data: {
            labels: bulanLabels,
            datasets: [ makeDataset(currentIndex) ]
        },
        options: {
            responsive: true,
            interaction: {
                mode: 'nearest',
                intersect: false
            },
            plugins: {
                legend: {
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            const val = Number(context.raw || 0);
                            const idx = context.dataIndex;
                            let label = `${context.dataset.label} - ${bulanLabels[idx]}: ${val.toFixed(2)}`;

                            if (idx > 0) {
                                const prev = Number(context.dataset.data[idx - 1] || 0);
                                const diff = val - prev;

                                let symbol = '●';
                                if (diff > 0) symbol = '▲';
                                else if (diff < 0) symbol = '▼';

                                // persen dibanding bulan sebelumnya (kalau prev != 0)
                                let persen = '';
                                if (prev !== 0) {
                                    const diffPct = (diff / Math.abs(prev)) * 100;
                                    persen = ` ${symbol} ${diffPct.toFixed(1)}%`;
                                } else if (diff !== 0) {
                                    persen = ` ${symbol}`;
                                }

                                label += persen;
                            }

                            return label;
                        }
                    }
                }
            },
            scales: {
            y: {
                beginAtZero: true,
                // max: 100,  // HAPUS / KOMENTAR baris ini
                title: {
                    display: true,
                    text: 'Total Skor'
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'Bulan'
                }
            }
        }
        }
    });

    // Ubah sales di dropdown -> update grafik
    selectSalesTrend.addEventListener('change', function () {
        currentIndex = parseInt(this.value) || 0;
        const ds = makeDataset(currentIndex);

        trendChart.data.datasets[0].label = ds.label;
        trendChart.data.datasets[0].data  = ds.data;
        trendChart.update();
    });

});
</script>
@endsection
