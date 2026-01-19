@extends('layouts.app')
@include('layouts.navbar')

@section('content')
<div class="container mt-4">
    <h4>Edit Status Penawaran {{ $penawaran->nomor_penawaran }}</h4>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- INFO PENAWARAN (READ ONLY) --}}
    <div class="card mb-3">
        <div class="card-header">
            Informasi Penawaran
        </div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-md-3">Nomor Penawaran</dt>
                <dd class="col-md-9">{{ $penawaran->nomor_penawaran }}</dd>

                <dt class="col-md-3">Tanggal Penawaran</dt>
                <dd class="col-md-9">{{ $penawaran->tanggal_penawaran }}</dd>

                <dt class="col-md-3">Customer</dt>
                <dd class="col-md-9">
                    {{ optional($penawaran->customer)->nama_customer ?? '-' }}
                </dd>

                <dt class="col-md-3">Sales</dt>
                <dd class="col-md-9">
                    {{ optional(optional($penawaran->sales)->user)->name ?? '-' }}
                </dd>

                <dt class="col-md-3">Total Harga</dt>
                <dd class="col-md-9">
                    Rp {{ number_format($penawaran->total_harga ?? 0, 0, ',', '.') }}
                </dd>

                <dt class="col-md-3">Status Saat Ini</dt>
                <dd class="col-md-9">
                    <span class="badge bg-secondary">{{ ucfirst($penawaran->status) }}</span>
                </dd>
            </dl>
        </div>
    </div>

    {{-- DETAIL ITEM (READ ONLY) --}}
    <div class="card mb-3">
        <div class="card-header">
            Detail Item Penawaran
        </div>
        <div class="card-body p-0">
            <div class="table-responsive mb-0">
                <table class="table table-bordered mb-0">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th width="80">Qty</th>
                            <th width="150">Harga Kesepakatan</th>
                            <th width="150">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($penawaran->details as $d)
                            @php
                                $sub = $d->subtotal ?? ($d->qty * $d->harga_kesepakatan);
                            @endphp
                            <tr>
                                <td>{{ $d->product_name }}</td>
                                <td>{{ $d->qty }}</td>
                                <td>Rp {{ number_format($d->harga_kesepakatan, 0, ',', '.') }}</td>
                                <td>Rp {{ number_format($sub, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center">Tidak ada detail penawaran.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- FORM UBAH STATUS (ADMIN) --}}
    <form action="{{ route('penawaran.update', ['id' => $penawaran->id, 'page' => $page ?? 1]) }}"
      method="POST">
    @csrf
    @method('PUT')

        <input type="hidden" name="page" value="{{ $page ?? 1 }}">

        <div class="mb-3">
            <label for="status">Status Penawaran</label>
            <select name="status" id="status" class="form-control" required>
                <option value="diajukan" {{ $penawaran->status === 'diajukan' ? 'selected' : '' }}>Diajukan</option>
                <option value="diterima" {{ $penawaran->status === 'diterima' ? 'selected' : '' }}>Diterima</option>
            </select>
            <small class="text-muted">
                Admin hanya mengubah status penawaran (diajukan / diterima).
            </small>
        </div>

        <div class="mb-3">
            <label for="keterangan">Keterangan (Opsional)</label>
            <textarea name="keterangan" id="keterangan" class="form-control" rows="3">{{ old('keterangan', $penawaran->keterangan) }}</textarea>
        </div>

        <button type="submit" class="btn btn-success">Simpan</button>
        <a href="{{ route('penawaran.index', ['page' => $page ?? 1]) }}" class="btn btn-secondary">Kembali</a>
    </form>
</div>
@endsection
