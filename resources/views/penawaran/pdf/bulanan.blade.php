<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Penawaran Bulanan</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 3px; }
        th { background: #eee; }
        .no-border td, .no-border th { border: none !important; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
    </style>
</head>
<body>

<h3 class="text-center">Laporan Penawaran Bulanan</h3>

<table class="no-border">
    <tr>
        <td style="width: 25%;">Sales</td>
        <td>: {{ optional($sales)->nama_sales ?? $user->name }}</td>
    </tr>
    <tr>
        <td>Periode</td>
        <td>: {{ sprintf('%02d', $bulan) }} / {{ $tahun }}</td>
    </tr>
    @if($customer)
    <tr>
        <td>Customer</td>
        <td>: {{ $customer->nama_customer }}</td>
    </tr>
    @endif
</table>

<br>

<table>
    <thead>
        <tr>
            <th style="width: 5%">No</th>
            <th style="width: 18%">Nomor Penawaran</th>
            <th style="width: 10%">Tanggal</th>
            <th>Customer</th>
            <th style="width: 10%">Qty Total</th>
            <th style="width: 15%">Total Harga</th>
            <th style="width: 10%">Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($penawaran as $i => $p)
        <tr>
            <td class="text-center">{{ $i+1 }}</td>
            <td>{{ $p->nomor_penawaran }}</td>
            <td class="text-center">{{ $p->tanggal_penawaran }}</td>
            <td>{{ optional($p->customer)->nama_customer }}</td>
            <td class="text-center">{{ $p->details->sum('qty') }}</td>
            <td class="text-right">Rp {{ number_format($p->total_harga, 0, ',', '.') }}</td>
            <td class="text-center">{{ ucfirst($p->status) }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="7" class="text-center">Tidak ada data penawaran</td>
        </tr>
        @endforelse
    </tbody>
</table>

</body>
</html>
