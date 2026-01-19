@extends('layouts.app')
@include('layouts.navbar')
@section('title', 'Master Customer')

@section('content')
<div class="container mt-4">
    <h4>Data Customer {{ Auth::user()->name }}</h4>
    <a href="{{ route('customer.create', ['page' => request('page', 1)]) }}" class="btn btn-primary mb-3">+ Tambah Customer</a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form action="{{ route('customer.index') }}" method="GET" class="form-inline mb-3">

        <input type="text" name="search" placeholder="Cari nama customer..." value="{{ request('search') }}" class="form-control mr-2">
        <button class="btn btn-primary" type="submit">Filter</button>
</form>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>No</th>
                <th>Nama</th>
                <th>Alamat</th>
                <th>Telepon</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
    @forelse ($customers as $c)
        <tr>
            <td>{{ $loop->iteration + ($customers->currentPage() - 1) * $customers->perPage() }}</td>
            <td>{{ $c->nama_customer }}</td>
            <td>{{ $c->alamat }}</td>
            <td>{{ $c->telepon }}</td>
            <td>

                <a href="{{ route('customer.edit', ['id' => $c->id, 'page' => request('page', 1)]) }}" class="btn btn-sm btn-warning">Edit</a>
                
                <form id="form-hapus-{{ $c->id }}" action="{{ route('customer.destroy', $c->id) }}" method="POST" style="display:inline;">
                @csrf
                @method('DELETE')
                <input type="hidden" name="page" value="{{ request('page') }}">
                <button type="button" class="btn btn-sm btn-danger" onclick="hapus({{ $c->id }})">Hapus</button>
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
        {{ $customers->withQueryString()->links() }}
    </div>
</div>
@endsection
<script>
function hapus(id) {
    Swal.fire({
        title: 'Apakah Anda yakin?',
        text: "Data Customer yang dihapus tidak bisa dikembalikan!",
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