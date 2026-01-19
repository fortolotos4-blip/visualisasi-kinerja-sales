@extends('layouts.app')
@include('layouts.navbar')

@section('content')
<div class="container">
    <h4>Daftar Penawaran</h4>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="GET" action="{{ route('penawaran.index') }}" class="form-inline mb-3">
        <input type="date" name="tanggal" value="{{ request('tanggal') }}" class="form-control mr-2">

        <select name="sales_id" class="form-control mr-2">
            <option value="">-- Pilih Sales --</option>
            @foreach($sales as $s)
                <option value="{{ $s->id }}" {{ request('sales_id') == $s->id ? 'selected' : '' }}>
                    {{ $s->nama_sales }}
                </option>
            @endforeach
        </select>

        <button type="submit" class="btn btn-primary">Filter</button>
    </form>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>No</th>
                <th>Nomor Penawaran</th>
                <th>Sales</th>
                <th>Customer</th>
                <th>Produk</th>
                <th>Jumlah</th>
                <th>Total Harga</th>
                <th>Tanggal</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($penawaran as $row)
                <tr>
                    <td>{{ ($penawaran->currentPage() - 1) * $penawaran->perPage() + $loop->iteration }}</td>
                    <td>{{ $row->nomor_penawaran }}</td>
                    <td>{{ optional(optional($row->sales)->user)->name ?? '-' }}</td>
                    <td>{{ optional($row->customer)->nama_customer ?? '-' }}</td>

                    <!-- Produk: tampilkan list nama x qty -->
                    <td>
                        @if($row->details->isEmpty())
                            -
                        @else
                            <ul class="mb-0 pl-3">
                                @foreach($row->details as $d)
                                    <li>{{ $d->product_name ?? '-' }} &times; {{ $d->qty }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </td>

                    <!-- Jumlah total (sum qty) -->
                    <td>{{ $row->details->sum('qty') }}</td>

                    <td>Rp{{ number_format($row->total_harga ?? 0, 0, ',', '.') }}</td>
                    <td>{{ $row->tanggal_penawaran }}</td>
                    <td>
                        <span class="badge 
                            @if($row->status == 'diajukan') bg-warning
                            @elseif($row->status == 'diterima') bg-success
                            @elseif($row->status == 'setuju') bg-info
                            @else bg-danger
                            @endif">
                            {{ ucfirst($row->status) }}
                        </span>
                    </td>
                    <td>
                        @if($row->status === 'setuju')
                            {{-- Untuk admin, route verifikasi/ubah biasanya adminEdit/adminUpdate --}}
                            <a href="{{ route('penawaran.edit', ['id' => $row->id, 'page' => $penawaran->currentPage()]) }}"
                            class="btn btn-sm btn-primary">
                                Konfirmasi
                            </a>
                        @elseif($row->status === 'batal' || $row->status === 'diterima')
                            <span class="text-secondary">Selesai</span>
                        @else
                            <span class="text-secondary">—</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="10" class="text-center">Belum ada data</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="d-flex justify-content-center">
        {{ $penawaran->appends(request()->query())->links() }}
    </div>
</div>
@endsection
