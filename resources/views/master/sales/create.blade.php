@extends('layouts.app')
@include('layouts.navbar')
@section('title', 'Tambah Sales')

@section('content')
<div class="container">
    <h4>Tambah Sales</h4>
    <form action="{{ route('sales.store') }}" method="POST">
        @csrf
        <div class="form-group">
            <label>Kode Sales</label>
            <input type="text" name="kode_sales" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Nama Sales</label>
            <input type="text" name="nama_sales" class="form-control" required>
        </div>
        <div class="form-group">
    <label for="wilayah_id">Wilayah</label>
    <select name="wilayah_id" class="form-control" required>
        <option value="">-- Pilih Wilayah --</option>
        @foreach ($wilayah as $w)
            <option value="{{ $w->id }}">{{ $w->nama_wilayah }}</option>
        @endforeach
    </select>
</div>
        <div class="form-group">
            <label>Target Penjualan</label>
            <input type="number" name="target_penjualan" class="form-control">
        </div>
        <button type="submit" class="btn btn-success">Simpan</button>
    </form>
</div>
@endsection
