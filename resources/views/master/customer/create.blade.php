@extends('layouts.app')
@include('layouts.navbar')
@section('title', 'Tambah Customer')

@section('content')
    <h4>Tambah Customer</h4>
    <form method="POST" action="{{ route('customer.store') }}">
        @csrf

        <div class="form-group">
            <label>Nama Customer</label>
            <input type="text" name="nama_customer" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Alamat</label>
            <input type="text" name="alamat" class="form-control">
        </div>
        <div class="form-group">
            <label>Telepon</label>
            <input type="tel" name="telepon" class="form-control" pattern="[0-9]+">
        </div>
        <div class="mb-3">
        <label for="status_customer">Status Customer</label>
        <select name="status_customer" class="form-control" required>
            <option value="">-- Pilih Status --</option>
            <option value="baru">Baru</option>
            <option value="lama">Lama</option>
        </select>
    </div>
        <button class="btn btn-success" type="submit">Simpan</button>
    </form>
@endsection
