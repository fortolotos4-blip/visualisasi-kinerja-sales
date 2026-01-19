@extends('layouts.app')
@include('layouts.navbar')

@section('content')
<div class="container mt-4">
    <h4>Edit Penawaran {{ $penawaran->nomor_penawaran }}</h4>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('penawaran.sales.update', $penawaran->id) }}?page={{ $page }}"
          method="POST" id="formEdit">
        @csrf
        @method('PUT')

        <input type="hidden" name="page" value="{{ $page }}">
        <input type="hidden" id="rowIndex" value="{{ $penawaran->details->count() }}">

        {{-- Header --}}
        <div class="row">
            <div class="col-md-3">
                <div class="mb-3">
                    <label>Tanggal Penawaran</label>
                    <input type="date" name="tanggal_penawaran" class="form-control"
                           value="{{ $penawaran->tanggal_penawaran }}" required>
                </div>
            </div>
        </div>

        <div class="mb-3">
            <label>Customer</label>
            <select name="customer_id" class="form-control" required>
                @foreach ($customers as $customer)
                    <option value="{{ $customer->id }}"
                        {{ $penawaran->customer_id == $customer->id ? 'selected' : '' }}>
                        {{ $customer->nama_customer }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- PILIH PRODUK UNTUK TAMBAH --}}
        <div class="card p-3 mb-3">
            <div class="row">
                <div class="col-md-6">
                    <label>Produk</label>
                    <select id="selectProduk" class="form-control">
                        <option value="">-- Pilih Produk --</option>
                        @foreach ($produk as $p)
                            <option value="{{ $p->id }}"
                                data-harga="{{ $p->harga }}"
                                data-satuan="{{ $p->satuan }}">
                                {{ $p->nama_produk }} — Rp {{ number_format($p->harga,0,',','.') }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label>Qty</label>
                    <input type="number" id="inputQty" class="form-control" value="1" min="1">
                </div>

                <div class="col-md-2">
                    <label>&nbsp;</label>
                    <button type="button" id="btnAdd" class="btn btn-primary btn-block">Tambah</button>
                </div>
            </div>
        </div>

        {{-- TABLE DETAIL --}}
        <div class="table-responsive mb-3">
            <table class="table table-bordered" id="tableDetail">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th width="80">Qty</th>
                        <th width="120">Harga Pabrik</th>
                        <th width="150">Harga Kesepakatan</th>
                        <th width="90">Satuan</th>
                        <th width="80">Aksi</th>
                    </tr>
                </thead>
                <tbody>

                    {{-- BARIS EXISTING --}}
                    @foreach ($penawaran->details as $i => $d)
                        <tr>
                            <td>
                                {{ $d->product_name }}
                                <input type="hidden" name="items[{{ $i }}][product_id]" value="{{ $d->product_id }}">
                            </td>

                            <td>
                                <input type="number" name="items[{{ $i }}][qty]"
                                    class="form-control"
                                    value="{{ $d->qty }}" min="1">
                            </td>

                            <td>Rp {{ number_format($d->harga_pabrik,0,',','.') }}</td>

                            <td>
                                <input type="number" name="items[{{ $i }}][harga_kesepakatan]"
                                       class="form-control"
                                       value="{{ $d->harga_kesepakatan }}" min="0">
                            </td>

                            <td>{{ $d->satuan }}</td>

                            <td>
                                <button type="button" class="btn btn-danger btn-sm btn-remove">Hapus</button>
                            </td>
                        </tr>
                    @endforeach

                </tbody>
            </table>
        </div>

        {{-- Diskon --}}
        <div class="mb-3">
            <label>Diskon Global (%)</label>
            <input type="number" name="diskon_global_pct" class="form-control"
                   value="{{ $penawaran->diskon_global_pct }}" min="0" max="10" step="0.01">
        </div>

        {{-- Keterangan --}}
        <div class="mb-3">
            <label>Keterangan</label>
            <textarea name="keterangan" class="form-control">{{ $penawaran->keterangan }}</textarea>
        </div>

        <button type="submit" class="btn btn-success">Update</button>
        <a href="{{ route('penawaran.sales.index',['page'=>$page]) }}" class="btn btn-secondary">Kembali</a>

    </form>
</div>

{{-- ================== SCRIPT TAMBAH BARIS ================== --}}
<script>
document.addEventListener("DOMContentLoaded", function () {

    const selectProduk = document.getElementById("selectProduk");
    const inputQty     = document.getElementById("inputQty");
    const btnAdd       = document.getElementById("btnAdd");
    const rowIndex     = document.getElementById("rowIndex");
    const tbody        = document.querySelector("#tableDetail tbody");

    btnAdd.addEventListener("click", function () {

        let pid = selectProduk.value;
        if (pid === "") {
            alert("Pilih produk dulu!");
            return;
        }

        let idx = parseInt(rowIndex.value);

        let nama      = selectProduk.options[selectProduk.selectedIndex].text.split(" — ")[0];
        let harga     = selectProduk.options[selectProduk.selectedIndex].getAttribute("data-harga");
        let satuan    = selectProduk.options[selectProduk.selectedIndex].getAttribute("data-satuan");
        let qty       = inputQty.value;

        // buat row baru
        let html = `
        <tr>
            <td>
                ${nama}
                <input type="hidden" name="items[${idx}][product_id]" value="${pid}">
            </td>

            <td>
                <input type="number" name="items[${idx}][qty]" class="form-control" value="${qty}" min="1">
            </td>

            <td>Rp ${new Intl.NumberFormat("id-ID").format(harga)}</td>

            <td>
                <input type="number" name="items[${idx}][harga_kesepakatan]" class="form-control" value="${harga}" min="0">
            </td>

            <td>${satuan}</td>

            <td>
                <button type="button" class="btn btn-danger btn-sm btn-remove">Hapus</button>
            </td>
        </tr>`;

        tbody.insertAdjacentHTML("beforeend", html);

        rowIndex.value = idx + 1; // increment index

        // reset input
        selectProduk.value = "";
        inputQty.value = 1;
    });

    // Hapus baris
    document.addEventListener("click", function (e) {
        if (e.target.classList.contains("btn-remove")) {
            e.target.closest("tr").remove();
        }
    });

});
</script>
@endsection
