@extends('layouts.app') {{-- Kalau kamu sudah punya layout --}}
@include('layouts.navbar')
@section('title', 'Tambah Produk')

@section('content')
<div class="container">
    <h4>Tambah Produk</h4>
    <form method="POST" action="{{ route('produk.store') }}">
        @csrf
        <div class="form-group">
            <label>Nama Produk</label>
            <input type="text" name="nama_produk" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Kode Produk</label>
            <input type="text" name="kode_produk" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Harga</label>
            <input type="number" name="harga" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Satuan</label>
            <input type="text" name="satuan" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">Simpan</button>
    </form>
</div>
@endsection
