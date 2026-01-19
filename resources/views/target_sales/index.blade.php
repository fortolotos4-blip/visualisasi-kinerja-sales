@extends('layouts.app')
@include('layouts.navbar')

@section('content')
<div class="container mt-4">
    <h4>Target Penjualan per Sales</h4>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="mb-3">
        <a href="{{ route('target_sales.create') }}" class="btn btn-primary">+ Tambah Target</a>
    </div>

    <form method="GET" action="{{ route('target_sales.index') }}" class="form-inline mb-3">
        <select name="bulan" class="form-control mr-2">
            @foreach(range(1, 12) as $b)
                <option value="{{ $b }}" {{ $month == $b ? 'selected' : '' }}>
                    {{ date('F', mktime(0,0,0,$b,1)) }}
                </option>
            @endforeach
        </select>

        <select name="tahun" class="form-control mr-2">
            @foreach(range(date('Y'), 2020) as $t)
                <option value="{{ $t }}" {{ $year == $t ? 'selected' : '' }}>
                    {{ $t }}
                </option>
            @endforeach
        </select>

        <button type="submit" class="btn btn-secondary">Tampilkan</button>
    </form>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th style="width:50px">No</th>
                        <th>Sales</th>
                        <th style="width:140px">Level</th>
                        <th style="width:200px">Target (Bulan {{ $month }} / {{ $year }})</th>
                        <th style="width:160px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($sales as $i => $s)
                    @php
                        // pastikan $targets/$levels adalah Collection
                        $targetsColl = is_array($targets) ? collect($targets) : $targets;
                        $levelsColl = is_array($levels) ? collect($levels) : $levels;

                        $ts = $targetsColl ? $targetsColl->get($s->id) : null;

                        $effective = null;
                        $source = null;

                        if ($ts) {
                            // handle jika $ts adalah array atau object
                            $effective = isset($ts->target) ? $ts->target : (isset($ts['target']) ? $ts['target'] : 0);
                            $source = isset($ts->source) ? $ts->source : (isset($ts['source']) ? $ts['source'] : 'override');
                        } else {
                            $lt = $levelsColl ? $levelsColl->get($s->level) : null;
                            $effective = $lt ? (isset($lt->amount) ? $lt->amount : (isset($lt['amount']) ? $lt['amount'] : 0)) : 0;
                            $source = 'default';
                        }
                    @endphp
                    <tr>
                        <td>{{ ($sales->currentPage()-1)*$sales->perPage() + $loop->iteration }}</td>
                        <td>{{ $s->nama_sales }}</td>
                        <td><span class="badge bg-info">{{ $s->level ?? '-' }}</span></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <form action="{{ route('target_sales.update', $s->id) }}" method="POST" class="d-flex align-items-center">
                                    @csrf
                                    <input type="hidden" name="tahun" value="{{ $year }}">
                                    <input type="hidden" name="bulan" value="{{ $month }}">
                                    <input 
                                        name="amount"
                                        type="text"
                                        class="form-control form-control-sm money-input"
                                        style="width:160px"
                                        value="{{ number_format($effective, 0, ',', '.') }}">
                                    <button class="btn btn-sm btn-primary ml-2" type="submit">Simpan</button>
                                </form>
                            </div>
                        </td>

                        <td>
                            @if(strtolower($source) === 'override')
                                {{-- FORM BATAL dengan SweetAlert --}}
                                <form action="{{ route('target_sales.reset', $s->id) }}"
                                      method="POST"
                                      class="form-batal d-inline"
                                      data-nama="{{ $s->nama_sales }}">
                                    @csrf
                                    <input type="hidden" name="tahun" value="{{ $year }}">
                                    <input type="hidden" name="bulan" value="{{ $month }}">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        Batal
                                    </button>
                                </form>
                            @else
                                <span class="text-secondary">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center">Belum ada sales.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3 d-flex justify-content-center">
        {{ $sales->withQueryString()->links() }}
    </div>
</div>
@endsection

@section('scripts')
    {{-- SweetAlert2 --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- Format input uang & SweetAlert Batal --}}
    <script>
    // Formatter input rupiah
    document.querySelectorAll('.money-input').forEach(function (input) {
        input.addEventListener('input', function () {
            let value = this.value.replace(/\D/g, ''); // buang semua huruf/titik/koma
            if (value === '') {
                this.value = '';
                return;
            }
            this.value = new Intl.NumberFormat('id-ID').format(value);
        });

        // Pada submit: ubah ke angka asli
        input.form?.addEventListener('submit', function () {
            document.querySelectorAll('.money-input').forEach(function (inp) {
                inp.value = inp.value.replace(/\D/g, ''); // kirim numeric ke backend
            });
        });
    });

    // SweetAlert untuk tombol Batal (reset override)
    document.querySelectorAll('.form-batal').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault(); // block submit biasa

            const nama = this.dataset.nama || 'sales ini';

            Swal.fire({
                title: 'Batalkan Target ?',
                text: 'Target untuk ' + nama + ' akan dihapus dan kembali ke awal.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, batalkan',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.submit(); // lanjut submit ke controller
                }
            });
        });
    });
    </script>
@endsection
