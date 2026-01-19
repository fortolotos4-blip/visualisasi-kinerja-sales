@extends('layouts.app')
@include('layouts.navbar')
@section('title', 'Tambah Wilayah')

@section('content')
<div class="container">
    <h4>Tambah Wilayah</h4>
    <form action="{{ route('wilayah.store') }}" method="POST">
        @csrf
        <div class="form-group">
            <label>Kode Wilayah</label>
            <input type="text" name="kode_wilayah" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Nama Wilayah</label>
            <input type="text" name="nama_wilayah" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-success">Simpan</button>
    </form>
</div>
@endsection
