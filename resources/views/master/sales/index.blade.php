@extends('layouts.app')
@include('layouts.navbar')
@section('title', 'Data Sales')

@section('content')
<div class="container">
    <h4>Data Sales</h4>
    <a href="{{ route('sales.create') }}" class="btn btn-primary mb-3">+ Tambah Sales</a>

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

                {{-- ⬇ Tambahkan tombol Edit dan Delete di sini --}}
        <td>

            <a href="{{ route('sales.edit', ['id' => $s->id, 'page' => request('page', 1)]) }}" class="btn btn-sm btn-warning">Edit</a>

            <form id="form-hapus-{{ $s->id }}" action="{{ route('sales.destroy', $s->id) }}" method="POST" style="display:inline;">
            @csrf
            @method('DELETE')
            <input type="hidden" name="page" value="{{ request('page') }}">
            <button type="button" class="btn btn-sm btn-danger" onclick="hapus({{ $s->id }})">Hapus</button>
            </form>

        </td>
            </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">Tidak ada data sales.</td>
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
function hapus(id) {
    Swal.fire({
        title: 'Apakah Anda yakin?',
        text: "Data Sales yang dihapus tidak bisa dikembalikan!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, hapus',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('form-hapus-' + id).submit();
        }
    });
}
</script>