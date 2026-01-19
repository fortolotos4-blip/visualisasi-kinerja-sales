@extends('layouts.app')
@include('layouts.navbar')
@section('title', 'Edit Produk')

@section('content')
<div class="container">
    <h4>Edit Data Produk</h4>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('produk.update', $produk->id) }}" method="POST">
        @csrf
        @method('PUT')

        <input type="hidden" name="page" value="{{ request('page') }}">
        
        <div class="mb-3">
            <label>Kode Produk</label>
            <input type="text" name="kode_produk" class="form-control" value="{{ $produk->kode_produk }}" readonly>
        </div>

        <div class="mb-3">
            <label>Nama Produk</label>
            <input type="text" name="nama_produk" class="form-control" value="{{ old('nama_produk', $produk->nama_produk) }}" required>
        </div>

        <div class="mb-3">
            <label>Harga</label>
            <input type="number" name="harga" class="form-control" value="{{ old('harga', $produk->harga) }}" required>
        </div>

        <button type="submit" class="btn btn-primary">Update</button>
        <a href="{{ route('produk.index') }}" class="btn btn-secondary">Kembali</a>
    </form>
</div>
@endsection
