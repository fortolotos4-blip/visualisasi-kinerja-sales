@extends('layouts.app')
@include('layouts.navbar')

@section('title', 'Input Kunjungan Sales')

@section('content')
<div class="container mt-4">
    <h4>Input Kunjungan Sales</h4>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('kunjungan.store') }}" method="POST">
    @csrf

    <div class="mb-3">
        <label for="customer_id">Customer</label>
        <select name="customer_id" id="customer_id" class="form-control" required>
            <option value="">-- Pilih Customer --</option>
            @foreach ($customers as $customer)
                <option value="{{ $customer->id }}">
                    {{ $customer->nama_customer }} 
                    ( {{ ucfirst($customer->status_customer) }})
                </option>
            @endforeach
        </select>
    </div>

    <div class="row">
            <div class="col-md-3">
                {{-- Tanggal Kunjungan --}}
            <label for="tanggal_kunjungan">Tanggal Kunjungan</label>
            <input type="date" name="tanggal_kunjungan" class="form-control" required>
        </div>
        </div>
        <div class="mb-3"></div>

    <!-- Tujuan -->
    <div class="mb-3">
        <label for="tujuan">Tujuan</label>
        <select name="tujuan" class="form-control" required>
            <option value="">-- Pilih Tujuan --</option>
            <option value="Mengenalkan Produk">Mengenalkan Produk</option>
            <option value="Follow Up">Follow Up Penawaran</option>
            <option value="Lain-Lain">Lain-lain</option>
        </select>
    </div>

    <!-- Status -->
    <div class="mb-3">
        <label for="status">Status</label>
        <select name="status" class="form-control" required>
            <option value="">-- Pilih Status --</option>
            <option value="Pending">Tindak Lanjut</option>
            <option value="Berhasil">Berhasil</option>
            <option value="Batal">Batal</option>
        </select>
    </div>

    <!-- Keterangan -->
    <div class="mb-3">
    <label for="keterangan">Keterangan (Opsional)</label>
    <textarea name="keterangan" id="keterangan" class="form-control" rows="3" placeholder="Tuliskan keterangan di sini..."></textarea>
    </div>

    <button type="submit" class="btn btn-primary">Simpan</button>
    <a href="{{ route('kunjungan.index') }}" class="btn btn-secondary">Kembali</a>
</form>

</div>
@endsection
