@extends('layouts.app')
@include('layouts.navbar')
@section('title', 'Data Wilayah')

@section('content')
<div class="container">
    <h4>Data Wilayah</h4>
    <a href="{{ route('wilayah.create') }}" class="btn btn-primary mb-3">+ Tambah Wilayah</a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form action="{{ route('wilayah.index') }}" method="GET" class="form-inline mb-3">

        <input type="text" name="search" class="form-control mr-2" placeholder="Cari nama / kode Wilayah..." value="{{ request('search') }}">
        <button class="btn btn-primary" type="submit">Cari</button>

</form>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>No</th>
                <th>Kode Wilayah</th>
                <th>Nama Wilayah</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($wilayah as $w)
            <tr>
                <td>{{ ($wilayah->currentPage() - 1) * $wilayah->perPage() + $loop->iteration }}</td>
                <td>{{ $w->kode_wilayah }}</td>
                <td>{{ $w->nama_wilayah }}</td>

                {{-- ⬇ Tambahkan tombol Edit dan Delete di sini --}}
        <td>
            <a href="{{ route('wilayah.edit', ['id' => $w->id, 'page' => request('page', 1)]) }}" class="btn btn-sm btn-warning">Edit</a>

            <form id="form-hapus-{{ $w->id }}" action="{{ route('wilayah.destroy', $w->id) }}" method="POST" style="display:inline;">
            @csrf
            @method('DELETE')
            <input type="hidden" name="page" value="{{ request('page') }}">
            <button type="button" class="btn btn-sm btn-danger" onclick="hapus({{ $w->id }})">Hapus</button>
            </form>

        </td>
            </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center">Tidak ada data wilayah.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <!-- PAGINATION DIBAWAH TABEL -->
    <div class="d-flex justify-content-center">
        {{ $wilayah->withQueryString()->links() }}
    </div>
</div>
@endsection
<script>
function hapus(id) {
    Swal.fire({
        title: 'Apakah Anda yakin?',
        text: "Data Wilayah yang dihapus tidak bisa dikembalikan!",
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