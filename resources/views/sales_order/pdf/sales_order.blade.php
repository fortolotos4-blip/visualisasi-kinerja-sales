<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sales Order {{ $order->nomor_so }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .no-border td, .no-border th {
            border: none !important;
        }
        th, td {
            border: 1px solid #000;
            padding: 5px;
        }
        th {
            background: #f1f1f1;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
    </style>
</head>
<body>

{{-- HEADER PERUSAHAAN --}}
<table class="no-border">
    <tr>
        <td style="width: 70%;">
            <strong style="font-size: 16px;">PT. Chop Indo Sejahtera</strong><br>
            Jl Raya Sukommanunggal Jaya Suarabaya, Indonesia 60188<br>
            Telp: (031)7325985 | Email: marketing@chopindo.co.id
        </td>
        <td style="width: 30%; text-align: right;">
            {{-- <img src="{{ public_path('logo.png') }}" width="100"> --}}
        </td>
    </tr>
</table>

<br>
<h3 class="text-center">SALES ORDER</h3>

{{-- INFO SO --}}
<table class="no-border">
    <tr>
        <td style="width: 25%;">Nomor SO</td>
        <td>: {{ $order->nomor_so }}</td>
    </tr>
    <tr>
        <td>Tanggal</td>
        <td>: {{ $order->tanggal }}</td>
    </tr>
    <tr>
        <td>Sales</td>
        <td>: {{ optional($order->sales)->nama_sales ?? '-' }}</td>
    </tr>
    <tr>
        <td>Customer</td>
        <td>: {{ optional($order->customer)->nama_customer ?? '-' }}</td>
    </tr>
    @if(!empty($order->keterangan))
    <tr>
        <td>Keterangan</td>
        <td>: {{ $order->keterangan }}</td>
    </tr>
    @endif
    <tr>
        <td>Status</td>
        <td>: {{ ucfirst($order->status) }}</td>
    </tr>
</table>

<br>

{{-- DETAIL ITEM --}}
<table>
    <thead>
        <tr>
            <th style="width: 5%;">No</th>
            <th>Produk</th>
            <th style="width: 10%;">Qty</th>
            <th style="width: 15%;">Harga</th>
            <th style="width: 20%;">Subtotal</th>
        </tr>
    </thead>
    <tbody>
        @php
            $rows = 0;
        @endphp

        @if($order->details && $order->details->count())
            @foreach($order->details as $i => $d)
                @php $rows++; @endphp
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td>{{ $d->product_name ?? optional($d->produk)->nama_produk ?? '-' }}</td>
                    <td class="text-center">{{ $d->qty }}</td>
                    <td class="text-right">
                        Rp {{ number_format($d->harga_satuan ?? 0, 0, ',', '.') }}
                    </td>
                    <td class="text-right">
                        Rp {{ number_format($d->subtotal ?? ($d->qty * ($d->harga_satuan ?? 0)), 0, ',', '.') }}
                    </td>
                </tr>
            @endforeach
        @else
            @php $rows = 1; @endphp
            <tr>
                <td class="text-center">1</td>
                <td>{{ optional($order->produk)->nama_produk ?? '-' }}</td>
                <td class="text-center">{{ $order->jumlah ?? 0 }}</td>
                <td class="text-right">
                    Rp {{ number_format($order->harga_satuan ?? 0, 0, ',', '.') }}
                </td>
                <td class="text-right">
                    Rp {{ number_format(($order->harga_satuan ?? 0) * ($order->jumlah ?? 0), 0, ',', '.') }}
                </td>
            </tr>
        @endif
    </tbody>
</table>

<br>

{{-- RINGKASAN TOTAL --}}
<table class="no-border" style="width: 40%; float: right;">
    <tr>
        <td>Total Bruto</td>
        <td class="text-right">
            Rp {{ number_format($order->total_bruto ?? 0, 0, ',', '.') }}
        </td>
    </tr>
    <tr>
        <td>Diskon ({{ $order->diskon_global_pct ?? 0 }}%)</td>
        <td class="text-right">
            Rp {{ number_format($order->diskon_global_rp ?? 0, 0, ',', '.') }}
        </td>
    </tr>
    <tr>
        <td>PPN 11%</td>
        <td class="text-right">
            Rp {{ number_format($order->ppn_rp ?? 0, 0, ',', '.') }}
        </td>
    </tr>
    <tr>
        <th>Grand Total</th>
        <th class="text-right">
            Rp {{ number_format($order->total_harga ?? 0, 0, ',', '.') }}
        </th>
    </tr>
    <tr>
        <td>Sisa Tagihan</td>
        <td class="text-right">
            Rp {{ number_format($order->sisa_tagihan ?? 0, 0, ',', '.') }}
        </td>
    </tr>
</table>

<div style="clear: both;"></div>
<br><br>

{{-- TANDA TANGAN --}}
<table class="no-border" style="width: 100%; margin-top: 40px;">
    <tr>
        <td style="width: 50%; text-align: center;">
            Mengetahui,<br>
            Admin<br><br><br><br>
            ( __________________________ )
        </td>
        <td style="width: 50%; text-align: center;">
            Sales,<br><br><br><br>
            ( __________________________ )<br>
            {{ optional($order->sales)->nama_sales ?? '-' }}
        </td>
    </tr>
</table>

</body>
</html>
