@extends('layouts.app')
@include('layouts.navbar')
@section('title', 'Daftar Produk')

@section('content')
<div class="container">
    <h4>Daftar Produk</h4>

    <a href="{{ route('produk.create') }}" class="btn btn-primary mb-3">+ Tambah Produk</a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form action="{{ route('produk.index') }}" method="GET" class="form-inline mb-3">

        <input type="text" name="search" class="form-control mr-2" placeholder="Cari nama / kode produk..." value="{{ request('search') }}">
        <button class="btn btn-primary" type="submit">Cari</button>

</form>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>No</th>
                <th>Nama</th>
                <th>Kode</th>
                <th>Harga</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($produk as $item)
            <tr>
                <td>{{ ($produk->currentPage() - 1) * $produk->perPage() + $loop->iteration }}</td>
                <td>{{ $item->nama_produk }}</td>
                <td>{{ $item->kode_produk }}</td>
                <td>Rp{{ number_format($item->harga, 0, ',', '.') }}</td>
                
                {{-- ⬇ Tambahkan tombol Edit dan Delete di sini --}}
        <td>
            <a href="{{ route('produk.edit', ['id' => $item->id, 'page' => request('page', 1)]) }}" class="btn btn-sm btn-warning">Edit</a>

            <form id="form-hapus-{{ $item->id }}" action="{{ route('produk.destroy', $item->id) }}" method="POST" style="display:inline;">
            @csrf
            @method('DELETE')
            <input type="hidden" name="page" value="{{ request('page') }}">
            <button type="button" class="btn btn-sm btn-danger" onclick="hapus({{ $item->id }})">Hapus</button>
            </form>

        </td>
            </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center">Tidak ada data customer.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <!-- PAGINATION DIBAWAH TABEL -->
    <div class="d-flex justify-content-center">
        {{ $produk->withQueryString()->links() }}
    </div>
</div>
@endsection
<script>
function hapus(id) {
    Swal.fire({
        title: 'Apakah Anda yakin?',
        text: "Data Produk yang dihapus tidak bisa dikembalikan!",
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