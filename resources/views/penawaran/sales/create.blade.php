@extends('layouts.app')
@include('layouts.navbar')

@section('content')
<div class="container mt-4">
    <h4>Buat Penawaran Baru</h4>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('penawaran.store') }}" method="POST" id="penawaranForm">
        @csrf

        <div class="row">
            <div class="col-md-3">
                <div class="mb-3">
                   <label for="tanggal_penawaran">Tanggal Penawaran</label>
                   <input type="date" name="tanggal_penawaran" class="form-control"
                          value="{{ old('tanggal_penawaran', date('Y-m-d')) }}" required>
                </div>
            </div>
        </div>

        <div class="mb-3">
            <label for="customer_id">Customer</label>
            <select name="customer_id" id="customer_id" class="form-control" required>
                <option value="">-- Pilih Customer --</option>
                @foreach ($customers as $customer)
                    <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                        {{ $customer->nama_customer }} ( {{ ucfirst($customer->status_customer) }} )
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Pemilihan produk untuk ditambahkan ke daftar --}}
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
                    {{-- teks kecil kode produk bisa juga ditampilkan di sini kalau ingin --}}
                </div>

                <div class="col-md-2 mb-2">
                    <label for="inputQty">Qty</label>
                    <input type="number" id="inputQty" class="form-control" value="1" min="1">
                </div>

                <div class="col-md-2 mb-2">
                    <label>&nbsp;</label>
                    <button type="button" id="btnAdd" class="btn btn-primary btn-block">Tambah</button>
                </div>
            </div>
            <small class="text-muted">Pilih produk, masukkan qty, lalu tekan <strong>Tambah</strong>.</small>
        </div>

        {{-- Tabel daftar item --}}
        <div class="mb-3">
            <h5>Daftar Item</h5>
            <div class="table-responsive">
                <table class="table table-bordered" id="itemsTable">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th width="90">Qty</th>
                            <th width="150">Harga Pabrik</th>
                            <th width="170">Harga Kesepakatan</th>
                            <th width="120">Satuan</th>
                            <th width="130">Subtotal</th>
                            <th width="80">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- baris ditambahkan via JS --}}
                    </tbody>
                </table>
            </div>

            {{-- Ringkasan total + diskon + PPN --}}
            <div class="text-right">
                <div>
                    <strong>Total Bruto:</strong>
                    Rp <span id="totalBrutoText">0</span>
                </div>

                <div class="mt-2">
                    <label class="mb-0" for="diskon_global_pct">Diskon Global (%)</label>
                    <input type="number"
                           name="diskon_global_pct"
                           id="diskon_global_pct"
                           class="form-control d-inline-block"
                           style="width: 100px;"
                           min="0" max="10" step="0.01"
                           value="{{ old('diskon_global_pct', 0) }}">
                    <small class="text-muted d-block">
                        Maksimal 10% dari total bruto
                    </small>
                    <div>
                        Diskon (Rp): Rp <span id="diskonRpText">0</span>
                    </div>
                </div>

                <div class="mt-2">
                    <div>
                        PPN 11%: Rp <span id="ppnText">0</span>
                    </div>
                    <h5 class="mt-2">
                        Grand Total: Rp <span id="grandTotalText">0</span>
                    </h5>
                </div>
            </div>
        </div>

        <div class="mb-3">
            <label for="keterangan">Keterangan (Opsional)</label>
            <textarea name="keterangan" class="form-control">{{ old('keterangan') }}</textarea>
        </div>

        <button type="submit" class="btn btn-success">Simpan</button>
        <a href="{{ route('penawaran.sales.index') }}" class="btn btn-secondary">Kembali</a>
    </form>

    {{-- Template row (tidak tampil). Kita pakai cloning via JS --}}
    <template id="rowTemplate">
        <tr>
            <td>
                <div class="product-name fw-bold"></div>
                <div class="text-muted small product-code"></div>
            </td>
            <td>
                <input type="number" class="form-control row-qty" value="1" min="1" />
            </td>
            <td class="td-harga-pabrik"></td>
            <td>
                <input type="number" class="form-control row-harga-kesepakatan" min="0" />
                <div class="text-danger small mt-1 row-warning" style="display:none;"></div>
            </td>
            <td class="td-satuan"></td>
            <td class="td-subtotal">Rp 0</td>
            <td>
                <button type="button" class="btn btn-danger btn-sm btn-remove">Hapus</button>
            </td>

            {{-- hidden inputs for submit --}}
            <input type="hidden" class="input-product-id" name="" value="" />
            <input type="hidden" class="input-harga-pabrik" name="" value="" />
            <input type="hidden" class="input-subtotal" name="" value="" />
        </tr>
    </template>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const productSelect    = document.getElementById('productSelect');
    const inputQty         = document.getElementById('inputQty');
    const btnAdd           = document.getElementById('btnAdd');
    const itemsTableBody   = document.querySelector('#itemsTable tbody');
    const rowTemplate      = document.getElementById('rowTemplate');

    const totalBrutoText   = document.getElementById('totalBrutoText');
    const diskonInput      = document.getElementById('diskon_global_pct');
    const diskonRpText     = document.getElementById('diskonRpText');
    const ppnText          = document.getElementById('ppnText');
    const grandTotalText   = document.getElementById('grandTotalText');

    let idxCounter = 0;

    function formatRupiah(number) {
        return new Intl.NumberFormat('id-ID').format(Math.round(number));
    }

    function recalcTotal() {
        let totalBruto = 0;
        document.querySelectorAll('.input-subtotal').forEach(el => {
            totalBruto += parseFloat(el.value) || 0;
        });

        // baca diskon global (%)
        let pct = parseFloat(diskonInput.value);
        if (isNaN(pct)) pct = 0;
        if (pct < 0) pct = 0;
        if (pct > 10) {
            pct = 10;
            diskonInput.value = 10;
        }

        const diskonRp = Math.round(totalBruto * pct / 100);
        const dpp      = totalBruto - diskonRp;
        const ppnRp    = Math.round(dpp * 0.11);
        const grandTotal = dpp + ppnRp;

        totalBrutoText.textContent = formatRupiah(totalBruto);
        diskonRpText.textContent   = formatRupiah(diskonRp);
        ppnText.textContent        = formatRupiah(ppnRp);
        grandTotalText.textContent = formatRupiah(grandTotal);
    }

    function createRow(productId, productName, kodeProduk, satuan, hargaPabrik, qty, hargaKesepakatan) {
        const clone = rowTemplate.content.cloneNode(true);
        const tr = clone.querySelector('tr');

        const curIdx = idxCounter++;

        // set produk + kode kecil di bawahnya
        tr.querySelector('.product-name').textContent = productName;
        tr.querySelector('.product-code').textContent = kodeProduk ? `Kode: ${kodeProduk}` : '';

        tr.querySelector('.td-harga-pabrik').textContent = 'Rp ' + formatRupiah(hargaPabrik);
        tr.querySelector('.row-qty').value = qty;
        tr.querySelector('.row-harga-kesepakatan').value = hargaKesepakatan;
        tr.querySelector('.td-satuan').textContent = satuan || '-';

        // hidden inputs name attributes (items[curIdx][...])
        tr.querySelector('.input-product-id').name = `items[${curIdx}][product_id]`;
        tr.querySelector('.input-product-id').value = productId;

        tr.querySelector('.input-harga-pabrik').name = `items[${curIdx}][harga_pabrik]`;
        tr.querySelector('.input-harga-pabrik').value = hargaPabrik;

        tr.querySelector('.input-subtotal').name = `items[${curIdx}][subtotal]`;

        // initial subtotal
        const initialSubtotal = qty * hargaKesepakatan;
        tr.querySelector('.input-subtotal').value = initialSubtotal;
        tr.querySelector('.td-subtotal').textContent = 'Rp ' + formatRupiah(initialSubtotal);

        // also set input names for qty, harga_kesepakatan, alasan jika masih dipakai
        tr.querySelector('.row-qty').name = `items[${curIdx}][qty]`;
        tr.querySelector('.row-harga-kesepakatan').name = `items[${curIdx}][harga_kesepakatan]`;
        // kalau alasan masih mau disimpan, bisa tambahkan input hidden/kolom lain

        const qtyInput      = tr.querySelector('.row-qty');
        const priceInput    = tr.querySelector('.row-harga-kesepakatan');
        const subtotalCell  = tr.querySelector('.td-subtotal');
        const subtotalHidden= tr.querySelector('.input-subtotal');
        const warningEl     = tr.querySelector('.row-warning');

        function onChangeCalc() {
            const q = parseInt(qtyInput.value) || 0;
            const price = parseFloat(priceInput.value) || 0;
            const subtotal = q * price;
            subtotalCell.textContent = 'Rp ' + formatRupiah(subtotal);
            subtotalHidden.value = subtotal;

            // cek policy harga (10% di bawah–atas)
            const maxDiscountPct = 0.10;
            const maxIncreasePct = 0.10;
            const minAllowed = Math.round(hargaPabrik * (1 - maxDiscountPct));
            const maxAllowed = Math.round(hargaPabrik * (1 + maxIncreasePct));

            if (price < minAllowed || price > maxAllowed) {
                warningEl.style.display = 'block';
                warningEl.textContent =
                    `Harga di luar batas kebijakan: Rp ${formatRupiah(minAllowed)} - Rp ${formatRupiah(maxAllowed)}`;
            } else {
                warningEl.style.display = 'none';
            }

            recalcTotal();
        }

        qtyInput.addEventListener('input', onChangeCalc);
        priceInput.addEventListener('input', onChangeCalc);

        // remove button
        tr.querySelector('.btn-remove').addEventListener('click', function () {
            tr.remove();
            recalcTotal();
        });

        itemsTableBody.appendChild(clone);
        recalcTotal();
    }

    btnAdd.addEventListener('click', function () {
        const opt = productSelect.options[productSelect.selectedIndex];
        const productId = opt.value;
        if (!productId) {
            alert('Pilih produk dulu.');
            return;
        }

        const productName = opt.text.split(' — ')[0].trim();
        const hargaAttr   = opt.getAttribute('data-harga');
        const kodeProduk  = opt.getAttribute('data-kode') || '';
        const satuan      = opt.getAttribute('data-satuan') || '-';
        const qty         = parseInt(inputQty.value) || 1;

        if (hargaAttr) {
            const harga = parseFloat(hargaAttr);
            createRow(productId, productName, kodeProduk, satuan, harga, qty, harga);
        } else {
            fetch(`/get-harga-produk/${productId}`)
                .then(res => res.json())
                .then(data => {
                    const harga = parseFloat(data.harga_satuan || 0);
                    createRow(productId, productName, kodeProduk, satuan, harga, qty, harga);
                });
        }

        // reset selector & qty
        productSelect.selectedIndex = 0;
        inputQty.value = 1;
    });

    // Recalc jika diskon diubah
    diskonInput.addEventListener('input', recalcTotal);

    // Prevent submitting when no items
    document.getElementById('penawaranForm').addEventListener('submit', function (e) {
        const rows = itemsTableBody.querySelectorAll('tr');
        if (rows.length === 0) {
            e.preventDefault();
            alert('Tambahkan minimal 1 produk ke penawaran.');
            return false;
        }
    });
});
</script>
@endsection
