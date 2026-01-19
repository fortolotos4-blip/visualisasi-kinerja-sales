@extends('layouts.app')
@include('layouts.navbar')

@section('content')
<div class="container">
    <h4>Tambah Pembayaran</h4>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('pembayaran.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        {{-- Pilih Sales Order --}}
        <div class="mb-3">
            <label for="sales_order_id">Pilih Sales Order</label>
            <select name="sales_order_id" id="sales_order_id" class="form-control" required>
                <option value="">-- Pilih SO --</option>
                @foreach($salesOrders as $so)
                    <option value="{{ $so->id }}" data-sisa="{{ $so->sisa_tagihan }}">
                        {{ $so->nomor_so }} 
                        
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Sisa Tagihan (readonly) --}}
        <div class="mb-3">
            <label>Sisa Tagihan</label>
            <input type="text" id="sisa_tagihan" class="form-control" readonly>
        </div>

        {{-- Jumlah Pembayaran --}}
        <div class="mb-3">
            <label>Jumlah yang akan dibayarkan</label>
            <input type="number" name="jumlah" id="jumlah" class="form-control" placeholder="0" min="0" required>
        </div>

        <div class="row">
            <div class="col-md-3">
                {{-- Tanggal Pembayaran --}}
                <label>Tanggal Pembayaran</label>
                <input type="date" name="tanggal_pembayaran" class="form-control" value="{{ date('Y-m-d') }}" required>
            </div>
        </div>
        <div class="mb-3"></div>

        {{-- Metode Pembayaran --}}
        <div class="mb-3">
            <label for="metode_pembayaran">Metode Pembayaran</label>
            <select name="metode_pembayaran" class="form-control" required>
                <option value="">-- Pilih --</option>
                <option value="transfer">Transfer</option>
            </select>
        </div>

        {{-- Bukti Pembayaran --}}
        <div class="mb-3">
            <label>Bukti Pembayaran</label>
            <input type="file" name="bukti" class="form-control">
        </div>

        {{-- Catatan --}}
        <div class="mb-3">
            <label>Catatan (Opsional)</label>
            <textarea name="catatan" class="form-control"></textarea>
        </div>

        <button type="submit" class="btn btn-success">Simpan</button>
        <a href="{{ route('pembayaran.index') }}" class="btn btn-secondary">Kembali</a>
    </form>
</div>

{{-- Script untuk tampilkan sisa tagihan & batasi jumlah --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectSO   = document.getElementById('sales_order_id');
    const sisaInput  = document.getElementById('sisa_tagihan');
    const jumlahInput = document.getElementById('jumlah');

    selectSO.addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];
        const sisa = parseFloat(selected.getAttribute('data-sisa')) || 0;

        if (sisa > 0) {
            // tampilkan sisa tagihan
            sisaInput.value = "Rp " + new Intl.NumberFormat('id-ID').format(sisa);

            // set nilai default jumlah & max agar tidak bisa melebihi sisa
            jumlahInput.value = sisa;
            jumlahInput.max   = sisa;
        } else {
            sisaInput.value   = "";
            jumlahInput.value = "";
            jumlahInput.removeAttribute('max');
        }
    });

    // optional: jaga-jaga kalau user ketik manual lebih besar daripada max
    jumlahInput.addEventListener('input', function () {
        const max = parseFloat(this.max);
        let val   = parseFloat(this.value) || 0;

        if (!isNaN(max) && val > max) {
            this.value = max;
        }
        if (val < 0) {
            this.value = 0;
        }
    });
});
</script>
@endsection
