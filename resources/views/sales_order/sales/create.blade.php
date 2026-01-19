@extends('layouts.app')
@include('layouts.navbar')

@section('title', 'Buat Sales Order')

@section('content')
<div class="container mt-4">
    <h4>Buat Sales Order</h4>
    <br>
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('sales-order.store') }}" id="soForm">
        @csrf

        {{-- Tanggal --}}
        <div class="row mb-3">
            <div class="col-md-3">
                <label for="tanggal">Tanggal</label>
                <input type="date" name="tanggal" class="form-control"
                       value="{{ old('tanggal', date('Y-m-d')) }}" required>
            </div>
        </div>

        {{-- Customer --}}
        <div class="mb-3">
            <label for="customer_id">Customer</label>
            <select name="customer_id" id="customer_id" class="form-control" required>
                <option value="">-- Pilih Customer --</option>
                @foreach ($customers as $customer)
                    <option value="{{ $customer->id }}"
                        {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                        {{ $customer->nama_customer }} ({{ ucfirst($customer->status_customer) }})
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Pilih Produk --}}
        <div class="card mb-3 p-3">
            <div class="form-row align-items-end">
                <div class="col-md-6 mb-2">
                    <label for="productSelect">Produk</label>
                    <select id="productSelect" class="form-control">
                        <option value="">-- Pilih Produk --</option>
                        @foreach ($produk as $item)
                            <option value="{{ $item->id }}"
                                    data-harga="{{ $item->harga }}"
                                    data-kode="{{ $item->kode_produk }}"
                                    data-satuan="{{ $item->satuan }}">
                                {{ $item->nama_produk }} — Rp {{ number_format($item->harga,0,',','.') }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2 mb-2">
                    <label for="inputQty">Qty</label>
                    <input type="number" id="inputQty" class="form-control"
                           value="1" min="1">
                </div>

                <div class="col-md-2 mb-2">
                    <label>&nbsp;</label>
                    <button type="button" id="btnAdd" class="btn btn-primary btn-block">
                        Tambah
                    </button>
                </div>
            </div>
            <small class="text-muted">
                Pilih produk, masukkan qty, lalu tekan <strong>Tambah</strong>.
            </small>
        </div>

        {{-- Tabel Item --}}
        <div class="mb-3">
            <h5>Daftar Item</h5>
            <div class="table-responsive">
                <table class="table table-bordered" id="itemsTable">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th width="90">Qty</th>
                            <th width="150">Harga Satuan</th>
                            <th width="140">Satuan</th>
                            <th width="130">Subtotal</th>
                            <th width="80">Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            {{-- Ringkasan Total --}}
            <div class="text-right">
                <div>
                    <strong>Sub Total / Total Bruto:</strong>
                    Rp <span id="totalBrutoText">0</span>
                </div>

                <div class="d-flex justify-content-end align-items-center mt-2 mb-1">
                    <label class="mb-0 mr-2" for="diskon_global_pct">Diskon Global (%)</label>
                    <input type="number"
                           name="diskon_global_pct"
                           id="diskon_global_pct"
                           class="form-control form-control-sm"
                           style="width:90px"
                           min="0"
                           max="10"
                           step="0.01"
                           value="{{ old('diskon_global_pct', 0) }}">
                    <span class="ml-2">
                        (-) Rp <span id="diskonRpText">0</span>
                    </span>
                </div>

                <div>
                    PPN 11%: Rp <span id="ppnText">0</span>
                </div>

                <h5 class="mt-2">
                    Grand Total: Rp <span id="grandTotalText">0</span>
                </h5>
            </div>
        </div>

        {{-- Keterangan --}}
        <div class="mb-3">
            <label for="keterangan">Keterangan (Opsional)</label>
            <textarea name="keterangan" class="form-control">{{ old('keterangan') }}</textarea>
        </div>

        <button type="submit" class="btn btn-success">Kirim Sales Order</button>
        <a href="{{ route('sales-order.my') }}" class="btn btn-secondary">Kembali</a>
    </form>

    {{-- Template row untuk item --}}
    <template id="rowTemplate">
        <tr>
            <td>
                <div class="td-name"></div>
                <div class="td-kode text-muted small"></div>
            </td>
            <td>
                <input type="number" class="form-control row-qty" value="1" min="1" />
            </td>
            <td class="td-harga">Rp 0</td>
            <td class="td-satuan"></td>
            <td class="td-subtotal">Rp 0</td>
            <td>
                <button type="button" class="btn btn-danger btn-sm btn-remove">
                    Hapus
                </button>
            </td>

            {{-- hidden inputs --}}
            <input type="hidden" class="input-product-id" name="" value="" />
            <input type="hidden" class="input-harga" name="" value="" />
            <input type="hidden" class="input-subtotal" name="" value="" />
            <input type="hidden" class="input-satuan" name="" value="" />
        </tr>
    </template>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const productSelect   = document.getElementById('productSelect');
    const inputQty        = document.getElementById('inputQty');
    const btnAdd          = document.getElementById('btnAdd');
    const itemsTableBody  = document.querySelector('#itemsTable tbody');
    const rowTemplate     = document.getElementById('rowTemplate');

    const totalBrutoText  = document.getElementById('totalBrutoText');
    const diskonInput     = document.getElementById('diskon_global_pct');
    const diskonRpText    = document.getElementById('diskonRpText');
    const ppnText         = document.getElementById('ppnText');
    const grandTotalText  = document.getElementById('grandTotalText');

    let idxCounter = 0;

    function formatRupiah(number) {
        return new Intl.NumberFormat('id-ID').format(Math.round(number));
    }

    function recalcTotal() {
        let totalBruto = 0;
        document.querySelectorAll('.input-subtotal').forEach(el => {
            totalBruto += parseFloat(el.value) || 0;
        });

        let diskonPct = parseFloat(diskonInput.value) || 0;
        if (diskonPct < 0)  diskonPct = 0;
        if (diskonPct > 10) diskonPct = 10;
        diskonInput.value = diskonPct.toString();

        const diskonRp = Math.round(totalBruto * diskonPct / 100);
        const dpp      = totalBruto - diskonRp;
        const ppn      = Math.round(dpp * 0.11);
        const grand    = dpp + ppn;

        totalBrutoText.textContent = formatRupiah(totalBruto);
        diskonRpText.textContent   = formatRupiah(diskonRp);
        ppnText.textContent        = formatRupiah(ppn);
        grandTotalText.textContent = formatRupiah(grand);
    }

    diskonInput.addEventListener('input', recalcTotal);

    function createRow(productId, productName, harga, qty, kode, satuan) {
        const clone  = rowTemplate.content.cloneNode(true);
        const tr     = clone.querySelector('tr');
        const curIdx = idxCounter++;

        // teks yang kelihatan
        tr.querySelector('.td-name').textContent   = productName;
        tr.querySelector('.td-kode').textContent   = 'Kode: ' + (kode || '-');
        tr.querySelector('.td-harga').textContent  = 'Rp ' + formatRupiah(harga);
        tr.querySelector('.row-qty').value         = qty;
        tr.querySelector('.td-satuan').textContent = satuan || '-';

        // ambil input
        const qtyInput       = tr.querySelector('.row-qty');
        const productIdInput = tr.querySelector('.input-product-id');
        const hargaInput     = tr.querySelector('.input-harga');
        const satuanInput    = tr.querySelector('.input-satuan');
        const subtotalInput  = tr.querySelector('.input-subtotal');
        const subtotalCell   = tr.querySelector('.td-subtotal');

        // SET NAME → ini yang penting untuk lolos validasi Laravel
        qtyInput.name          = `items[${curIdx}][qty]`;
        productIdInput.name    = `items[${curIdx}][product_id]`;
        hargaInput.name        = `items[${curIdx}][harga_satuan]`;
        satuanInput.name       = `items[${curIdx}][satuan]`;
        subtotalInput.name     = `items[${curIdx}][subtotal]`;

        // set value hidden
        productIdInput.value   = productId;
        hargaInput.value       = harga;
        satuanInput.value      = satuan || '';

        // subtotal awal
        const initialSubtotal  = qty * harga;
        subtotalInput.value    = initialSubtotal;
        subtotalCell.textContent = 'Rp ' + formatRupiah(initialSubtotal);

        // kalau qty diubah
        function onChangeCalc() {
            const q  = parseInt(qtyInput.value) || 0;
            const p  = parseFloat(hargaInput.value) || 0;
            const st = q * p;
            subtotalCell.textContent = 'Rp ' + formatRupiah(st);
            subtotalInput.value      = st;
            recalcTotal();
        }

        qtyInput.addEventListener('input', onChangeCalc);

        // tombol hapus
        tr.querySelector('.btn-remove').addEventListener('click', function () {
            tr.remove();
            recalcTotal();
        });

        itemsTableBody.appendChild(clone);
        recalcTotal();
    }

    btnAdd.addEventListener('click', function () {
        const opt       = productSelect.options[productSelect.selectedIndex];
        const productId = opt.value;

        if (!productId) {
            alert('Pilih produk dulu.');
            return;
        }

        const productName = opt.text.split(' — ')[0].trim();
        const hargaAttr   = opt.getAttribute('data-harga');
        const kode        = opt.getAttribute('data-kode');
        const satuan      = opt.getAttribute('data-satuan');
        const qty         = parseInt(inputQty.value) || 1;

        if (hargaAttr) {
            createRow(productId, productName, parseFloat(hargaAttr), qty, kode, satuan);
        } else {
            fetch(`/get-harga-produk/${productId}`)
                .then(res => res.json())
                .then(data => {
                    createRow(
                        productId,
                        productName,
                        parseFloat(data.harga_satuan || 0),
                        qty,
                        kode,
                        satuan
                    );
                });
        }

        productSelect.selectedIndex = 0;
        inputQty.value = 1;
    });

    document.getElementById('soForm').addEventListener('submit', function (e) {
        const rows = itemsTableBody.querySelectorAll('tr');
        if (rows.length === 0) {
            e.preventDefault();
            alert('Tambahkan minimal 1 produk.');
            return false;
        }
    });
});
</script>

@endsection
