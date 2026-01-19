@extends('layouts.app')
@include('layouts.navbar')
@section('title', 'Edit Wilayah')

@section('content')
<div class="container">
    <h4>Edit Data Wilayah</h4>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('wilayah.update', $wilayah->id) }}" method="POST">
        @csrf
        @method('PUT')
        <input type="hidden" name="page" value="{{ request('page') }}">

        <div class="mb-3">
            <label>Kode Wilayah</label>
            <input type="text" name="kode_wilayah" class="form-control" value="{{ $wilayah->kode_wilayah }}" readonly>
        </div>

        <div class="mb-3">
            <label>Nama Wilayah</label>
            <input type="text" name="nama_wilayah" class="form-control" value="{{ old('nama_wilayah', $wilayah->nama_wilayah) }}" required>
        </div>

        <button type="submit" class="btn btn-primary">Update</button>
        <a href="{{ route('wilayah.index') }}" class="btn btn-secondary">Kembali</a>
    </form>
</div>
@endsection
