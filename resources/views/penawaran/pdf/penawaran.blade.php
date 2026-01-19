<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Penawaran {{ $penawaran->nomor_penawaran }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
        }
        .header-table, .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .title {
            text-align: center;
            font-weight: bold;
            font-size: 18px;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #000;
            padding: 5px;
        }
        th {
            background: #eee;
        }
        .no-border td, .no-border th {
            border: none !important;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
    </style>
</head>
<body>

{{-- ========== HEADER PERUSAHAAN ========== --}}
<table class="no-border">
    <tr>
        <td style="width: 70%;">
            <strong style="font-size: 16px;">PT. Chop Indo Sejahtera</strong><br>
            Jl Raya Sukommanunggal Jaya Suarabaya, Indonesia 60188<br>
            Telp: (031)7325985 | Email: marketing@chopindo.co.id
        </td>

        {{-- Jika ingin pakai logo tinggal kirim filenya --}}
        <td style="width: 30%; text-align: right;">
            {{-- <img src="{{ public_path('logo.png') }}" width="100"> --}}
        </td>
    </tr>
</table>

<br>

<div class="title">SURAT PENAWARAN HARGA</div>

{{-- ========== INFO PENAWARAN ========== --}}
<table class="info-table no-border">
    <tr>
        <td style="width: 25%;">Nomor Penawaran</td>
        <td>: {{ $penawaran->nomor_penawaran }}</td>
    </tr>
    <tr>
        <td>Tanggal</td>
        <td>: {{ $penawaran->tanggal_penawaran }}</td>
    </tr>
    <tr>
        <td>Customer</td>
        <td>: {{ optional($penawaran->customer)->nama_customer }}</td>
    </tr>
    <tr>
        <td>Sales</td>
        <td>: {{ optional($sales)->nama_sales ?? $user->name }}</td>
    </tr>
</table>

<br>

{{-- ========== TABEL PRODUK ========== --}}
<table>
    <thead>
        <tr>
            <th style="width: 5%;">No</th>
            <th>Nama Produk</th>
            <th style="width: 10%;">Qty</th>
            <th style="width: 15%;">Harga</th>
            <th style="width: 20%;">Subtotal</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($penawaran->details as $i => $d)
        <tr>
            <td class="text-center">{{ $i+1 }}</td>
            <td>{{ $d->product_name }}</td>
            <td class="text-center">{{ $d->qty }}</td>
            <td class="text-right">Rp {{ number_format($d->harga_kesepakatan, 0, ',', '.') }}</td>
            <td class="text-right">Rp {{ number_format($d->subtotal, 0, ',', '.') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<br>

{{-- ========== TOTALAN ========== --}}
<table class="no-border" style="width: 40%; float: right;">
    <tr>
        <td>Total Bruto</td>
        <td class="text-right">Rp {{ number_format($penawaran->total_bruto, 0, ',', '.') }}</td>
    </tr>
    <tr>
        <td>Diskon ({{ $penawaran->diskon_global_pct }}%)</td>
        <td class="text-right">Rp {{ number_format($penawaran->diskon_global_rp, 0, ',', '.') }}</td>
    </tr>
    <tr>
        <td>PPN 11%</td>
        <td class="text-right">Rp {{ number_format($penawaran->ppn_rp, 0, ',', '.') }}</td>
    </tr>
    <tr>
        <th>Grand Total</th>
        <th class="text-right">Rp {{ number_format($penawaran->total_harga, 0, ',', '.') }}</th>
    </tr>
</table>

<div style="clear: both;"></div>
<br><br>

{{-- ========== TANDA TANGAN ========== --}}
<table class="no-border" style="width:100%; margin-top: 40px;">
    <tr>
        <td style="width: 50%; text-align: center;">
            Customer,<br><br><br><br>
            ( __________________________ )<br>
            {{ optional($penawaran->customer)->nama_customer }}
        </td>

        <td style="width: 50%; text-align: center;">
            Sales,<br><br><br><br>
            ( __________________________ )<br>
            {{ optional($sales)->nama_sales ?? $user->name }}
        </td>
    </tr>
</table>

</body>
</html>
