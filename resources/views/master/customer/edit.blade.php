@extends('layouts.app')
@include('layouts.navbar')
@section('title', 'Edit Data Customer')

@section('content')
<div class="container">
    <h4>Edit Data Customer</h4>

    <form action="{{ route('customer.update', $customer->id) }}" method="POST">
        @csrf
        @method('PUT')

        <input type="hidden" name="page" value="{{ $page }}">

        <div class="form-group">
            <label>Nomor Telepon</label>
            <input type="tel" name="telepon" class="form-control" pattern="[0-9]+" value="{{ $customer->telepon }}">
        </div>

        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
    </form>
</div>
@endsection
