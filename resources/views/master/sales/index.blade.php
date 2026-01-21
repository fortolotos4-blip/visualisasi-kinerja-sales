@extends('layouts.app')
@include('layouts.navbar')
@section('title', 'Data Sales')

@section('content')
<div class="container">
    <h4>Data Sales</h4>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form action="{{ route('sales.index') }}" method="GET" class="form-inline mb-3">

        <input type="text" name="search" class="form-control mr-2" placeholder="Cari nama / kode sales..." value="{{ request('search') }}">
        <button class="btn btn-primary" type="submit">Cari</button>

</form>
    <table class="table table-bordered table-striped">
    <thead>
        <tr>
            <th>No</th>
            <th>Kode Sales</th>
            <th>Nama Sales</th>
            <th>Wilayah</th>
            <th>Target Penjualan</th>
            <th>Status</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        @forelse($sales as $s)
            <tr>
                <td>{{ ($sales->currentPage() - 1) * $sales->perPage() + $loop->iteration }}</td>
                <td>{{ $s->kode_sales }}</td>
                <td>{{ $s->nama_sales }}</td>
                <td>{{ $s->wilayah->nama_wilayah ?? '-' }}</td> {{-- akses relasi --}}
                <td>{{ number_format($s->target_penjualan, 0, ',', '.') }}</td>
                <td>
                    @if($s->is_active)
                        <span class="badge badge-success">Aktif</span>
                    @else
                        <span class="badge badge-secondary">Nonaktif</span>
                    @endif
                </td>
                {{-- ⬇ Tambahkan tombol Edit dan Non Aktif di sini --}}
        <td>
            <a href="{{ route('sales.edit', ['id' => $s->id, 'page' => request('page', 1)]) }}"
            class="btn btn-sm btn-warning">
                Edit
            </a>

            <form id="form-status-{{ $s->id }}"
                action="{{ route('sales.toggle-status', $s->id) }}"
                method="POST"
                style="display:inline;">
                @csrf
                @method('PATCH')

                <button type="button"
                    class="btn btn-sm {{ $s->is_active ? 'btn-danger' : 'btn-success' }}"
                    onclick="toggleStatus({{ $s->id }}, '{{ $s->is_active ? 'nonaktifkan' : 'aktifkan' }}')">
                    {{ $s->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                </button>
            </form>
        </td>
            </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">Tidak ada data sales.</td>
                </tr>
        @endforelse
    </tbody>
</table>
<!-- PAGINATION DIBAWAH TABEL -->
    <div class="d-flex justify-content-center">
        {{ $sales->withQueryString()->links() }}
    </div>
</div>
@endsection
<script>
function toggleStatus(id, action) {
    Swal.fire({
        title: 'Apakah Anda yakin?',
        text: action === 'nonaktifkan'
            ? 'Sales ini akan dinonaktifkan dan tidak bisa login.'
            : 'Sales ini akan diaktifkan kembali.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: action === 'nonaktifkan' ? '#d33' : '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, ' + action,
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('form-status-' + id).submit();
        }
    });
}
</script>
