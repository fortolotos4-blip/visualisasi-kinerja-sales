@extends('layouts.app')
@include('layouts.navbar')

@section('content')
<div class="container mt-4">
    <h4>Target Penjualan per Sales</h4>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="mb-3">
        <a href="{{ route('target_sales.create') }}" class="btn btn-primary">
            + Tambah Target Default
        </a>
    </div>

    {{-- FILTER --}}
    <form method="GET" class="form-inline mb-3">
        <select name="bulan" class="form-control mr-2">
            @foreach(range(1,12) as $b)
                <option value="{{ $b }}" {{ $month == $b ? 'selected' : '' }}>
                    {{ date('F', mktime(0,0,0,$b,1)) }}
                </option>
            @endforeach
        </select>

        <select name="tahun" class="form-control mr-2">
            @for($t=date('Y');$t>=2020;$t--)
                <option value="{{ $t }}" {{ $year == $t ? 'selected' : '' }}>
                    {{ $t }}
                </option>
            @endfor
        </select>

        <button class="btn btn-secondary">Tampilkan</button>
    </form>

    {{-- TABLE --}}
    <div class="card">
        <div class="card-body p-0">
            <table class="table table-bordered table-striped mb-0">
                <thead>
                    <tr>
                        <th width="50">No</th>
                        <th>Sales</th>
                        <th width="150">Level</th>
                        <th width="220">Target ({{ $month }}/{{ $year }})</th>
                        <th width="150">Aksi</th>
                    </tr>
                </thead>
                <tbody>
@forelse($sales as $s)
@php
    $ts = $targets->get($s->id);
    $lt = $s->level && isset($levels[$s->level]) ? $levels[$s->level] : null;

    $effective = $ts ? $ts->target : ($lt->amount ?? 0);
    $source = $ts->source ?? 'default';
@endphp

<tr>
    <td>{{ ($sales->currentPage()-1)*$sales->perPage() + $loop->iteration }}</td>
    <td>{{ $s->nama_sales }}</td>

    {{-- LEVEL + TARGET FORM --}}
    <td colspan="2">
        <form action="{{ route('target_sales.update', $s->id) }}"
              method="POST"
              class="form-update-target d-flex align-items-center"
              data-sales="{{ $s->nama_sales }}"
              data-old-level="{{ $s->level ?? '' }}">
            @csrf

            <input type="hidden" name="tahun" value="{{ $year }}">
            <input type="hidden" name="bulan" value="{{ $month }}">
            <input type="hidden" name="level" class="hidden-level">

            <select class="form-control form-control-sm level-select mr-2" required>
                <option value="">-- Level --</option>
                @foreach($levels as $lvl)
                    <option value="{{ $lvl->level }}"
                        {{ $s->level === $lvl->level ? 'selected' : '' }}>
                        {{ $lvl->level }}
                    </option>
                @endforeach
            </select>

            <input type="text"
                   name="amount"
                   class="form-control form-control-sm money-input mr-2"
                   style="width:140px"
                   value="{{ number_format($effective,0,',','.') }}"
                   required>

            <button class="btn btn-sm btn-primary">Simpan</button>
        </form>
    </td>

    {{-- AKSI --}}
    <td>
        @if($source === 'override')
        <form action="{{ route('target_sales.reset', $s->id) }}"
              method="POST"
              class="form-batal"
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
<tr>
    <td colspan="5" class="text-center">Belum ada sales</td>
</tr>
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
document.querySelectorAll('.money-input').forEach(input => {

    // hanya angka saat mengetik
    input.addEventListener('input', function () {
        this.value = this.value.replace(/\D/g,'');
    });

    // format saat keluar input
    input.addEventListener('blur', function () {
        if(this.value){
            this.value = new Intl.NumberFormat('id-ID').format(this.value);
        }
    });

    // sebelum submit → kirim angka murni
    input.closest('form').addEventListener('submit', function () {
        input.value = input.value.replace(/\D/g,'');
    });
});


// submit + konfirmasi level
document.querySelectorAll('.form-update-target').forEach(f=>{
    f.addEventListener('submit',e=>{
        e.preventDefault();

        const sales=f.dataset.sales;
        const oldLevel=f.dataset.oldLevel||'-';
        const sel=f.querySelector('.level-select');
        const newLevel=sel.value;

        if(!newLevel){
            Swal.fire('Level wajib dipilih','','warning');
            return;
        }

        f.querySelector('.hidden-level').value=newLevel;

        if(oldLevel!==newLevel){
            Swal.fire({
                title:'Ubah Level Sales?',
                html:`<b>${sales}</b><br>${oldLevel} → ${newLevel}`,
                icon:'warning',
                showCancelButton:true,
                confirmButtonText:'Ya, Simpan'
            }).then(r=>r.isConfirmed && f.submit());
        }else{
            f.submit();
        }
    });
});

// reset
document.querySelectorAll('.form-batal').forEach(f=>{
    f.addEventListener('submit',e=>{
        e.preventDefault();
        Swal.fire({
            title:'Batalkan target?',
            icon:'warning',
            showCancelButton:true,
            confirmButtonText:'Ya'
        }).then(r=>r.isConfirmed && f.submit());
    });
});
</script>
@endsection
