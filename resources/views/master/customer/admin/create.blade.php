@extends('layouts.app')
@include('layouts.navbar')

@section('content')
<div class="container mt-4">
    <h4>Tambah Customer Baru (Admin)</h4>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('customer.admin.store') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label for="nama_customer">Nama Customer</label>
            <input type="text" name="nama_customer" class="form-control" value="{{ old('nama_customer') }}" required>
        </div>

        <div class="mb-3">
            <label for="alamat">Alamat</label>
            <textarea name="alamat" class="form-control" required>{{ old('alamat') }}</textarea>
        </div>

        <div class="mb-3">
            <label for="telepon">No. HP</label>
            <input type="tel" name="telepon" class="form-control" value="{{ old('telepon') }}" pattern="[0-9]+" required>
        </div>

        <div class="mb-3">
            <label for="status_customer">Status Customer</label>
            <select name="status_customer" class="form-control" required>
                <option value="baru" {{ old('status_customer') == 'baru' ? 'selected' : '' }}>Baru</option>
                <option value="lama" {{ old('status_customer') == 'lama' ? 'selected' : '' }}>Lama</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="user_id">Sales</label>
            <select name="user_id" class="form-control" required>
                <option value="">-- Pilih Sales --</option>
                @foreach ($sales as $sale)
                    <option value="{{ $sale->id }}" {{ old('user_id') == $sale->id ? 'selected' : '' }}>
                        {{ $sale->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="{{ route('customer.admin.index') }}" class="btn btn-secondary">Kembali</a>
    </form>
</div>
@endsection
