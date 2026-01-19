@extends('layouts.app')
@include('layouts.navbar')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="container mt-4">
    <h4>Daftar Sales — Manager</h4>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form method="GET" action="{{ route('master.sales.manager.index') }}" class="form-inline mb-3">
        <input type="text" name="search" class="form-control mr-2" placeholder="Cari nama / kode..." value="{{ request('search') }}">
        <button class="btn btn-primary">Cari</button>
    </form>

   <!-- mulai: tabel sales (ganti blok lama dengan ini) -->
<style>
    /* styling cepat untuk rapi di semua ukuran */
    .sales-table td, .sales-table th {
        vertical-align: middle !important;
    }

    /* batasi lebar kolom aksi supaya tombol tidak meluber */
    .col-aksi { width: 60px;}

    /* nama sales tidak memecah kata, potong jika terlalu panjang */
    .nama-sales { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 220px; display: inline-block; }

    /* badge level kecil dan kontras */
    .badge-level { padding: .35em .6em; font-size: .8rem; }

    /* badge performa (teks deskripsi kecil) */
    .perform-desc { font-size: .82rem; color: #666; line-height: 1.1; }

    /* buat cell action align kanan */
    .td-action { text-align: right; }

    /* pada layar kecil, badge & info disusun vertikal */
    @media (max-width: 767px) {
        .nama-sales { max-width: 120px; }
        .perform-desc { display: block; margin-top: .25rem; }
        .col-aksi { width: auto; }
    }
</style>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped mb-0 sales-table">
                <thead>
                    <tr>
                        <th style="width:56px">No</th>
                        <th>Sales</th>
                        <th>Wilayah</th>
                        <th style="width:120px">Level</th>
                        <th>Performa</th>
                        <th class="col-aksi">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($salesList as $sItem)
                        @php
                            $s = $sItem->model;
                            $eligible = $sItem->eligible;
                            $details = $sItem->details;
                        @endphp
                        <tr>
                            <td>{{ ($salesList->currentPage()-1)*$salesList->perPage() + $loop->iteration }}</td>

                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="me-2">
                                        {{-- kalau ada avatar, tampilkan di sini --}}
                                    </div>
                                    <div>
                                        <div class="nama-sales" title="{{ $s->nama_sales }}">{{ $s->nama_sales }}</div>
                                    </div>
                                </div>
                            </td>

                            <td>{{ $s->wilayah->nama_wilayah ?? '-' }}</td>

                            <td>
                                <span class="badge bg-info badge-level">{{ $s->level ?? '-' }}</span>
                            </td>

                            <td>
                                @if(!empty($details['eligible']))
                                    <div>
                                        <span class="badge bg-success">Layak Promosi</span>
                                    </div>
                                    <div class="perform-desc mt-1">
                                        {{ $details['reason'] ?? 'Kriteria terpenuhi' }}:
                                        <strong>{{ $details['matched_window'][0] ?? '-' }}</strong>
                                        &nbsp;→&nbsp;
                                        <strong>{{ $details['matched_window'][1] ?? '-' }}</strong>
                                        &nbsp;(<small>{{ $details['window_size'] ?? 3 }} bulan</small>)
                                    </div>
                                @else
                                    <div>
                                        <span class="badge bg-secondary">Belum</span>
                                    </div>
                                    <div class="perform-desc mt-1">Proses Berjalan</div>
                                @endif
                            </td>

                            <td class="td-action">
                                <div class="d-flex justify-content-end align-items-center gap-2">
                                    @if($eligible)
                                        <form method="POST" class="form-promote" action="{{ route('master.sales.manager.promote', $s->id) }}">
                                        @csrf
                                        <button type="button" class="btn btn-sm btn-success btn-promote"
                                            data-sales="{{ $s->nama_sales }}">
                                            Promosi
                                        </button>
                                    </form>
                                    @else
                                        <button class="btn btn-sm btn-outline-secondary" disabled>Promosi</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">Belum ada data sales.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<!-- akhir: tabel sales -->
    <div class="mt-3 d-flex justify-content-center">{{ $salesList->links() }}</div>
</div>
@endsection
<script>
document.addEventListener("DOMContentLoaded", function () {

    document.querySelectorAll('.btn-promote').forEach(btn => {
        btn.addEventListener('click', function () {

            let salesName = this.getAttribute('data-sales');
            let form = this.closest('form');

            Swal.fire({
                title: 'Konfirmasi Promosi',
                html: "Promosikan <b>" + salesName + "</b> ke level berikutnya?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Promosikan',
                cancelButtonText: 'Batal',
                reverseButtons: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });

        });
    });

});
</script>
