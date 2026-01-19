@extends('layouts.app')
@include('layouts.navbar')

@section('content')
<div class="container mt-4">
    <h4>Daftar Kontribusi Parameter</h4>

    <a href="{{ route('kontribusi_parameters.create') }}" class="btn btn-primary mb-3">+ Tambah Parameter</a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="GET" action="{{ route('kontribusi_parameters.index') }}" class="form-inline mb-3">
        <input type="number" name="periode_tahun" min="2020" class="form-control mr-2" placeholder="Cari tahun" value="{{ request('periode_tahun') }}">
        <button class="btn btn-primary" type="submit">Filter</button>
    </form>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Tahun</th>
                <th>Bobot Kunjungan</th>
                <th>Bobot Penawaran</th>
                <th>Bobot Penjualan</th>
                <th>Target Kunjungan</th>
                <th>Target Penawaran</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($parameters as $param)
            <tr>
                <td>{{ $param->periode_tahun }}</td>
                <td>{{ $param->bobot_kunjungan }}%</td>
                <td>{{ $param->bobot_penawaran }}%</td>
                <td>{{ $param->bobot_penjualan }}%</td>
                <td>{{ $param->target_kunjungan }}</td>
                <td>{{ $param->target_penawaran }}</td>
                <td>
                    @php
                            if ($param->status === 'Aktif') {
                                $badge = 'success';
                            } elseif ($param->status === 'Nonaktif') {
                                $badge = 'danger';
                            } else {

                            }
                        @endphp
                        <span class="badge bg-{{ $badge }}">{{ ucfirst($param->status) }}</span>
                    </td>
                </td>
                    <td>@if($param->status === 'Aktif')
                    <button type="button" class="btn btn-sm btn-danger" onclick="konfirmasiBatal({{ $param->periode_tahun }})">Batal</button>
                        <form id="form-batal-{{ $param->periode_tahun }}" action="{{ route('kontribusi_parameters.batal', $param->periode_tahun) }}" method="POST" style="display:none;">
                            @csrf
                        </form>
                    @elseif($param->status === 'Nonaktif')
                        <span class="text-secondary">Dibatalkan</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center">Belum ada data parameter.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="d-flex justify-content-center">
        {{ $parameters->links() }}
    </div>
</div>

<script>
function konfirmasiBatal(periode_tahun) {
    Swal.fire({
        title: 'Batalkan parameter ?',
        text: "Indikator Penilaian " + periode_tahun + " ini akan dibatalkan!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya',
        cancelButtonText: 'Tidak'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('form-batal-' + periode_tahun).submit();
        }
    });
}
</script>
@endsection
