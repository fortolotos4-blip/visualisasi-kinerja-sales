@if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@extends('layouts.app')
@include('layouts.navbar')

@section('content')
<div class="container">
    <h2>Tambah Kontribusi Parameter</h2>

    <form action="{{ route('kontribusi_parameters.store') }}" method="POST">
        @csrf

        {{-- Pilih Periode (bulan & tahun) --}}
        <div class="form-group">
            <label>Periode</label>
            <input type="number" name="periode_tahun" id="periode_tahun" class="form-control" min="2020" max="{{ date('Y') + 5 }}" required>
            <small class="form-text text-muted">Pilih tahun</small>
        </div>

        <hr>

        {{-- Bobot (%) --}}
        <div class="row">
            <div class="col-md-4">
                <label>Bobot Kunjungan (%)</label>
                <input type="number" name="bobot_kunjungan" step="0.01" min="0" max="100" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label>Bobot Penawaran (%)</label>
                <input type="number" name="bobot_penawaran" step="0.01" min="0" max="100" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label>Bobot Penjualan (%)</label>
                <input type="number" name="bobot_penjualan" step="0.01" min="0" max="100" class="form-control" required>
            </div>
        </div>

        <hr>

        {{-- Target --}}
        <div class="row">
            <div class="col-md-4">
                <label>Target Kunjungan</label>
                <input type="number" name="target_kunjungan" min="0" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label>Target Penawaran</label>
                <input type="number" name="target_penawaran" min="0" class="form-control" required>
            </div>
        </div>

        <hr>

        <button class="btn btn-success">Simpan</button>
        <a href="{{ route('kontribusi_parameters.index') }}" class="btn btn-secondary">Kembali</a>
    </form>
</div>
@endsection
<script>
    // Isi otomatis tahun sekarang
    document.getElementById('periode_tahun').value = new Date().getFullYear();
</script>