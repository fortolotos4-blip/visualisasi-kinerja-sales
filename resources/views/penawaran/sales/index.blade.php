@extends('layouts.app')
@include('layouts.navbar')

@section('content')
<div class="container mt-4">
    <h4>Daftar Penawaran {{ Auth::user()->name }}</h4>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <a href="{{ route('penawaran.sales.create') }}" class="btn btn-primary mb-3">+ Buat Penawaran</a>

    <form method="GET" action="{{ route('penawaran.sales.index') }}" class="form-inline mb-3">
    <input type="date" name="tanggal" value="{{ request('tanggal') }}" class="form-control mr-2">

    <select name="customer_id" class="form-control mr-2">
        <option value="">-- Pilih Customer --</option>
        @foreach($customers as $c)
            <option value="{{ $c->id }}" {{ request('customer_id') == $c->id ? 'selected' : '' }}>
                {{ $c->nama_customer }}
            </option>
        @endforeach
    </select>

    <button type="submit" class="btn btn-primary mr-2">Cari</button>

    {{-- 🔹 Tombol cetak bulanan (berdasarkan bulan & tahun dari tanggal filter) --}}
    <button type="button" class="btn btn-success" onclick="cetakBulanan()">Cetak</button>
</form>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>No</th>
                <th>Nomor Penawaran</th>
                <th>Tanggal</th>
                <th>Customer</th>
                <th>Produk</th>
                <th>Jumlah</th>
                <th>Total Harga</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($penawaran as $p)
                <tr>
                    <td>{{ ($penawaran->currentPage() - 1) * $penawaran->perPage() + $loop->iteration }}</td>
                    <td>{{ $p->nomor_penawaran }}</td>
                    <td>{{ $p->tanggal_penawaran }}</td>
                    <td>{{ optional($p->customer)->nama_customer ?? '-' }}</td>

                    <!-- Tampilkan semua produk + qty dalam satu kolom -->
                    <td>
                        @if($p->details->isEmpty())
                            -
                        @else
                            <ul class="mb-0 pl-3">
                                @foreach($p->details as $d)
                                    <li>{{ $d->product_name ?? '-' }} &times; {{ $d->qty }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </td>

                    <!-- Jumlah total keseluruhan (sum qty) -->
                    <td>{{ $p->details->sum('qty') }}</td>

                    <td>Rp {{ number_format($p->total_harga ?? 0, 0, ',', '.') }}</td>
                    <td>
                        @php
                            if ($p->status === 'diajukan') {
                                $badge = 'warning text-dark';
                            } elseif ($p->status === 'setuju') {
                                $badge = 'info';
                            } elseif ($p->status === 'diterima') {
                                $badge = 'success';
                            } elseif ($p->status === 'batal') {
                                $badge = 'danger';
                            } else {
                                $badge = 'secondary';
                            }
                        @endphp
                        <span class="badge bg-{{ $badge }}">{{ ucfirst($p->status) }}</span>
                    </td>
                    <td>
                        @if ($p->status === 'diajukan')
                        {{-- 🔹 Tombol EDIT (hanya kalau status diajukan/pending) --}}
                            <a href="{{ route('penawaran.sales.edit', ['id' => $p->id, 'page' => $penawaran->currentPage()]) }}"
                            class="btn btn-sm btn-warning">
                                Edit
                            </a>
                            
                            <button type="button" class="btn btn-sm btn-primary" onclick="konfirmasiCustomer({{ $p->id }})">Konfirmasi</button>

                            <form id="form-setuju-{{ $p->id }}" action="{{ route('penawaran.sales.convert', $p->id) }}" method="POST" style="display:none;">
                                @csrf
                            </form>

                            <form id="form-tolak-{{ $p->id }}" action="{{ route('penawaran.sales.reject', $p->id) }}" method="POST" style="display:none;">
                                @csrf
                            </form>

                        @elseif ($p->status === 'setuju')
                            <span class="text-secondary">Diproses Admin</span>

                        @elseif ($p->status === 'batal')
                            <span class="text-secondary">Customer batal</span>

                        @elseif ($p->status === 'diterima')
                            {{-- 🔹 tombol cetak per penawaran, hanya kalau status diterima --}}
                            <a href="{{ route('penawaran.sales.cetak', $p->id) }}"
                            class="btn btn-sm btn-outline-secondary"
                            target="_blank">
                                Cetak
                            </a>
                        @else
                            <span class="text-secondary">Sudah diproses</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">Belum ada penawaran.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <!-- PAGINATION DIBAWAH TABEL -->
    <div class="d-flex justify-content-center">
        {{ $penawaran->appends(request()->query())->links() }}
    </div>
</div>
@endsection

@section('scripts')
<script>
function konfirmasiCustomer(id) {
    Swal.fire({
        title: 'Konfirmasi Customer',
        text: 'Apakah customer setuju dengan penawaran ini?',
        icon: 'question',
        showDenyButton: true,
        confirmButtonText: 'Ya',
        denyButtonText: 'Tidak'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('form-setuju-' + id).submit();
        } else if (result.isDenied) {
            document.getElementById('form-tolak-' + id).submit();
        }
    });
}

function hapusPenawaran(id) {
    Swal.fire({
        title: 'Apakah Anda yakin?',
        text: "Data penawaran yang dihapus tidak bisa dikembalikan!",
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

// 🔹 Cetak bulanan: pakai bulan & tahun dari input tanggal + filter customer
function cetakBulanan() {
    var tanggal    = document.querySelector('input[name="tanggal"]').value;
    var customerId = document.querySelector('select[name="customer_id"]').value;

    var url = '{{ route('penawaran.sales.cetak.bulanan') }}';
    var params = [];

    if (tanggal) {
        params.push('tanggal=' + encodeURIComponent(tanggal));
    }
    if (customerId) {
        params.push('customer_id=' + encodeURIComponent(customerId));
    }

    if (params.length > 0) {
        url += '?' + params.join('&');
    }

    window.open(url, '_blank');
}
</script>
@endsection
