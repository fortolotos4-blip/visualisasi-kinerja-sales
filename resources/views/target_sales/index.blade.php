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
            @forelse($sales as $s)
            @php
                $ts = $targets->get($s->id);
                $effective = $ts?->target ?? ($levels[$s->level]->amount ?? 0);
                $source = $ts?->source ?? 'default';
            @endphp

            <tr>
                <td>{{ ($sales->currentPage()-1)*$sales->perPage() + $loop->iteration }}</td>
                <td>{{ $s->nama_sales }}</td>

                {{-- FORM UTAMA (LEVEL + TARGET) --}}
                <form action="{{ route('target_sales.update', $s->id) }}"
                    method="POST"
                    class="form-update-target"
                    data-sales="{{ $s->nama_sales }}"
                    data-old-level="{{ $s->level ?? '' }}">
                    @csrf

                    <input type="hidden" name="tahun" value="{{ $year }}">
                    <input type="hidden" name="bulan" value="{{ $month }}">
                    <input type="hidden" name="level" class="hidden-level">

                    {{-- LEVEL --}}
                    <td>
                        <select class="form-control form-control-sm level-select" required>
                            <option value="">-- Pilih Level --</option>
                            @foreach($levels as $lvl)
                                <option value="{{ $lvl->level }}"
                                    {{ $s->level === $lvl->level ? 'selected' : '' }}>
                                    {{ $lvl->level }}
                                </option>
                            @endforeach
                        </select>
                    </td>

                    {{-- TARGET --}}
                    <td>
                        <div class="d-flex align-items-center">
                            <input type="text"
                                name="amount"
                                class="form-control form-control-sm money-input"
                                style="width:150px"
                                value="{{ number_format($effective, 0, ',', '.') }}"
                                required>
                            <button class="btn btn-sm btn-primary ml-2">Simpan</button>
                        </div>
                    </td>
                </form>

                {{-- AKSI --}}
                <td>
                    @if($source === 'override')
                        <form action="{{ route('target_sales.reset', $s->id) }}"
                            method="POST"
                            class="form-batal d-inline"
                            data-nama="{{ $s->nama_sales }}">
                            @csrf
                            <input type="hidden" name="tahun" value="{{ $year }}">
                            <input type="hidden" name="bulan" value="{{ $month }}">
                            <button class="btn btn-sm btn-outline-danger">Batal</button>
                        </form>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-center">Belum ada sales</td></tr>
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// format rupiah
document.querySelectorAll('.money-input').forEach(inp => {
    inp.addEventListener('input', function () {
        this.value = this.value.replace(/\D/g,'');
        this.value = new Intl.NumberFormat('id-ID').format(this.value);
    });
});

// submit + konfirmasi level
document.querySelectorAll('.form-update-target').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const salesName = this.dataset.sales;
        const oldLevel  = this.dataset.oldLevel || '-';
        const select    = this.querySelector('.level-select');
        const newLevel  = select.value;

        if (!newLevel) {
            Swal.fire('Level wajib dipilih', '', 'warning');
            return;
        }

        this.querySelector('.hidden-level').value = newLevel;

        if (oldLevel !== newLevel) {
            Swal.fire({
                title: 'Ubah Level Sales?',
                html: `
                    <b>${salesName}</b><br>
                    ${oldLevel} → ${newLevel}
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Simpan',
                cancelButtonText: 'Batal'
            }).then(r => {
                if (r.isConfirmed) {
                    this.submit();
                }
            });
        } else {
            this.submit();
        }
    });
});

// reset override
document.querySelectorAll('.form-batal').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Batalkan target?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya',
            cancelButtonText: 'Batal'
        }).then(r => r.isConfirmed && this.submit());
    });
});
</script>
@endsection
