@extends('layouts.app')
@include('layouts.navbar')

@section('content')
<div class="container">
    <h4>Edit Data Sales</h4>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('sales.update', $sales->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label>Kode Sales</label>
            <input type="text" name="kode_sales" class="form-control" value="{{ $sales->kode_sales }}" readonly>
        </div>

        <div class="mb-3">
            <label>Nama Sales</label>
            <input type="text" name="nama_sales" class="form-control" value="{{ old('nama_sales', $sales->nama_sales) }}" required>
        </div>
        <div class="form-group">
    <label for="wilayah_id">Wilayah</label>
    <select name="wilayah_id" id="wilayah_id" class="form-control">
        <option value="">-- Pilih Wilayah --</option>
        @foreach ($wilayah as $item)
            <option value="{{ $item->id }}" {{ optional($sales)->wilayah_id == $item->id ? 'selected' : '' }}>
                {{ $item->nama_wilayah }}
            </option>
        @endforeach
    </select>
</div>

        <div class="mb-3">
            <label>Target Penjualan</label>
            <input type="number" name="target_penjualan" class="form-control" value="{{ old('target_penjualan', $sales->target_penjualan) }}" readonly>
        </div>

        <button type="submit" class="btn btn-primary">Update</button>
        <a href="{{ route('sales.index') }}" class="btn btn-secondary">Kembali</a>
    </form>
</div>
@endsection
