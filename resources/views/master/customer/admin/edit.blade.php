@extends('layouts.app')
@include('layouts.navbar')

@section('content')
<div class="container mt-4">
    <h4>Edit Data Customer (Admin)</h4>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('customer.admin.update', $customer->id) }}" method="POST">
        @csrf
        @method('PUT')

        <input type="hidden" name="page" value="{{ $page }}">

        <div class="mb-3">
            <label for="nama_customer" class="form-label">Nama Customer</label>
            <input type="text" name="nama_customer" class="form-control" value="{{ old('nama_customer', $customer->nama_customer) }}" required>
        </div>

        <div class="mb-3">
            <label for="alamat" class="form-label">Alamat</label>
            <textarea name="alamat" class="form-control" required>{{ old('alamat', $customer->alamat) }}</textarea>
        </div>

        <div class="mb-3">
            <label for="telepon" class="form-label">Nomor HP</label>
            <input type="text" name="telepon" class="form-control" pattern="[0-9]+" value="{{ old('telepon', $customer->telepon) }}" required>
        </div>

        <div class="mb-3">
            <label for="user_id" class="form-label">Sales (User)</label>
            <select name="user_id" class="form-control" required>
                <option value="">-- Pilih Sales --</option>
                @foreach ($sales as $salesman)
                    <option value="{{ $salesman->id }}" {{ old('user_id', $customer->user_id) == $salesman->id ? 'selected' : '' }}>
                        {{ $salesman->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="status_customer" class="form-label">Tipe Customer</label>
            <select name="status_customer" class="form-control" required>
                <option value="">-- Pilih Tipe --</option>
                <option value="baru" {{ old('tipe_customer', $customer->tipe_customer) == 'baru' ? 'selected' : '' }}>Baru</option>
                <option value="lama" {{ old('tipe_customer', $customer->tipe_customer) == 'lama' ? 'selected' : '' }}>Lama</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Update</button>
        <a href="{{ route('customer.admin.index') }}" class="btn btn-secondary">Kembali</a>
    </form>
</div>
@endsection
