@extends('layouts.app')
@include('layouts.navbar')

@section('content')
<div class="container">
    <h4 class="mb-4">Data Customer (Admin)</h4>

    <a href="{{ route('customer.admin.create', ['page' => request('page', 1)]) }}" class="btn btn-primary mb-3">+ Tambah Customer</a>
    
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="GET" action="{{ route('customer.admin.index') }}" class="form-inline mb-3">

        <input type="text" name="search" placeholder="Cari nama customer..." value="{{ request('search') }}" class="form-control mr-2">

        <select name="sales_id" class="form-control mr-2">
            <option value="">-- Pilih Sales --</option>
            @foreach($sales as $s)
                <option value="{{ $s->id }}" {{ request('sales_id') == $s->id ? 'selected' : '' }}>
                    {{ $s->nama_sales }}
                </option>
            @endforeach
        </select>
        
        <button class="btn btn-primary">Filter</button>

    </form>
    <table class="table table-bordered table-striped">
        <thead>
    <tr>
        <th>No</th>
        <th>Nama Customer</th>
        <th>Alamat</th>
        <th>No. HP</th>
        <th>Status</th>
        <th>Sales</th>
        <th>Aksi</th>
    </tr>
    </thead>
        <tbody>
            @forelse($customers as $cr)
    <tr>
        <td>{{ $loop->iteration + ($customers->currentPage() - 1) * $customers->perPage() }}</td>
        <td>{{ $cr->nama_customer }}</td>
        <td>{{ $cr->alamat }}</td>
        <td>{{ $cr->telepon ?? '-' }}</td>
        <td>{{ ucfirst($cr->status_customer) }}</td>
        <td>{{ $cr->user->name ?? '-' }}</td>
        <td>
            <a href="{{ route('customer.admin.edit', ['id' => $cr->id, 'page' => request('page', 1)]) }}" class="btn btn-sm btn-warning">Edit</a>

            <form id="form-hapus-{{ $cr->id }}" action="{{ route('customer.admin.destroy', $cr->id) }}" method="POST" style="display:inline;">
            @csrf
            @method('DELETE')
            <input type="hidden" name="page" value="{{ request('page') }}">
            <button type="button" class="btn btn-sm btn-danger" onclick="hapus({{ $cr->id }})">Hapus</button>
            </form>

        </td>
    </tr>
    @empty
    <tr>
        <td colspan="7" class="text-center">Tidak ada data customer.</td>
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