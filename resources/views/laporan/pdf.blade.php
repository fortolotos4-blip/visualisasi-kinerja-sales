<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Penjualan Seluruh Sales</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 6px; text-align: center; }
        th { background-color: #f2f2f2; }
        h3 { text-align: center; margin-bottom: 0; }
    </style>
</head>
<body>
    <h3>Laporan Penjualan Seluruh Sales - Bulan {{ $bulan }}/{{ $tahun }}</h3>
    <table>
        <thead>
            <tr>
                <th>Nama Sales</th>
                <th>Kunjungan</th>
                <th>Penawaran</th>
                <th>Sales Order</th>
                <th>Total Penjualan</th>
                <th>Total Pembayaran</th>
                <th>Piutang</th>
            </tr>
        </thead>
        <tbody>
            @foreach($laporan as $row)
                <tr>
                    <td>{{ $row['nama'] }}</td>
                    <td>{{ $row['kunjungan'] }}</td>
                    <td>{{ $row['penawaran'] }}</td>
                    <td>{{ $row['so'] }}</td>
                    <td>Rp{{ number_format($row['total_penjualan'], 0, ',', '.') }}</td>
                    <td>Rp{{ number_format($row['total_pembayaran'], 0, ',', '.') }}</td>
                    <td>Rp{{ number_format($row['piutang'], 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
