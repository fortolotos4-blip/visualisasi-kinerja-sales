@extends('layouts.app')
@include('layouts.navbar')

@section('content')
<div class="container">
    <h4>Verifikasi Pembayaran</h4>

    @if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

    <form action="{{ route('pembayaran.admin.update', $pembayaran->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label>No Sales Order</label>
            <input type="text" class="form-control" value="{{ $pembayaran->salesOrder->nomor_so }}" disabled>
        </div>

        <div class="mb-3">
            <label>Jumlah</label>
            <input type="text" class="form-control" value="Rp{{ number_format($pembayaran->jumlah, 0, ',', '.') }}" disabled>
        </div>

        <div class="mb-3">
            <label>Tanggal Bayar</label>
            <input type="text" class="form-control" value="{{ $pembayaran->tanggal_pembayaran }}" disabled>
        </div>
        <div class="mb-3">
    <label>Bukti Pembayaran</label><br>
    @if ($pembayaran->bukti)
        <a href="{{ asset('storage/' . $pembayaran->bukti) }}" target="_blank">
            <img src="{{ asset('storage/' . $pembayaran->bukti) }}" 
                 alt="Bukti Pembayaran" 
                 style="max-width: 300px; border-radius: 8px; border: 1px solid #ccc;">
        </a>
        <p class="text-muted mt-2">Klik gambar untuk memperbesar</p>
    @else
        <p class="text-danger">Tidak ada bukti pembayaran yang diunggah.</p>
    @endif
</div>
        <div class="mb-3">
            <label>Status Pembayaran</label>
            <select name="status" class="form-control" required>
                <option value="">-- Pilih --</option>
                <option value="diterima" {{ $pembayaran->status == 'diterima' ? 'selected' : '' }}>Diterima</option>
            </select>
        </div>

        <div class="mb-3">
            <label>Catatan (Opsional)</label>
            <textarea name="catatan" class="form-control" rows="3">{{ old('catatan', $pembayaran->catatan) }}</textarea>
        </div>

        <input type="hidden" name="page" value="{{ request('page') }}">

        <button type="submit" class="btn btn-success">Simpan Verifikasi</button>
        <a href="{{ route('pembayaran.admin.index', ['page' => request('page')]) }}" class="btn btn-secondary">Kembali</a>
    </form>
</div>
@endsection
