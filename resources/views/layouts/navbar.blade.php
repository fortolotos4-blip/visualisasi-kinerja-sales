<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <a class="navbar-brand" href="">Sistem Sales</a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarMain">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarMain">
        <ul class="navbar-nav mr-auto">
            @if(Auth::user()->jabatan === 'admin')
            <li class="nav-item"><a class="nav-link" href="{{ route('dashboard.admin') }}">Dashboard</a></li>
                <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" role="button"
                   data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Menu Master
                </a>
                <div class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
                    <a class="dropdown-item" href="{{ route('produk.index') }}">Produk</a>
                    <a class="dropdown-item" href="{{ route('customer.admin.index') }}">Customer</a>
                    <a class="dropdown-item" href="{{ route('sales.index') }}">Sales</a>
                    <a class="dropdown-item" href="{{ route('wilayah.index') }}">Wilayah</a>
                </div>
            </li>
            <li class="nav-item"><a class="nav-link" href="{{ route('penawaran.index') }}">Penawaran</a></li>
            <li class="nav-item"><a class="nav-link" href="{{ route('sales-order.index') }}">Sales Order</a></li>
            <li class="nav-item"><a class="nav-link" href="{{ route('pembayaran.admin.index') }}">Pembayaran</a></li>
            <li class="nav-item"><a class="nav-link" href="{{ route('penjualan.index') }}">Data Penjualan</a></li>
            <li class="nav-item"><a class="nav-link" href="{{ route('laporan.admin') }}">Laporan</a></li>
            @elseif(Auth::user()->jabatan === 'manajer')
                <li class="nav-item"><a class="nav-link" href="{{ route('kontribusi_parameters.index') }}">Indikator Penilaian</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('dashboard.manager') }}">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('master.sales.manager.index') }}">Sales</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('customer.manager.index') }}">Customer</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('target_sales.index') }}">Target Sales</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('penjualan.manager') }}">Penjualan</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('laporan.manager') }}">Laporan</a></li>
            @elseif(Auth::user()->jabatan === 'sales')
            @php
            $sales = Auth::user()->sales ?? null;
            $isPendingSales = $sales && is_null($sales->wilayah_id);
            @endphp

            <li class="nav-item">
            <a class="nav-link {{ $isPendingSales ? 'disabled text-muted pe-none' : '' }}"
            href="{{ $isPendingSales ? '#' : route('customer.index') }}">
                Customer
            </a></li>
            <li class="nav-item">
            <a class="nav-link {{ $isPendingSales ? 'disabled text-muted pe-none' : '' }}"
            href="{{ $isPendingSales ? '#' : route('penawaran.sales.index') }}">
                Penawaran
            </a></li>
            <li class="nav-item">
            <a class="nav-link {{ $isPendingSales ? 'disabled text-muted pe-none' : '' }}"
            href="{{ $isPendingSales ? '#' : route('kunjungan.index') }}">
                Kunjungan
            </a></li>
            <li class="nav-item">
            <a class="nav-link {{ $isPendingSales ? 'disabled text-muted pe-none' : '' }}"
            href="{{ $isPendingSales ? '#' : route('sales-order.my') }}">
                Sales Order
            </a></li>
            <li class="nav-item">
            <a class="nav-link {{ $isPendingSales ? 'disabled text-muted pe-none' : '' }}"
            href="{{ $isPendingSales ? '#' : route('pembayaran.index') }}">
                Pembayaran
            </a></li>
            <li class="nav-item">
            <a class="nav-link {{ $isPendingSales ? 'disabled text-muted pe-none' : '' }}"
            href="{{ $isPendingSales ? '#' : route('laporan.sales') }}">
                Laporan
            </a></li>
            @endif
        </ul>

        <ul class="navbar-nav ml-auto">
            <li class="nav-item">
                <span class="navbar-text text-light mr-3">
                    {{ Auth::user()->name }} ({{ Auth::user()->jabatan }})
                </span>
            </li>
            <li class="nav-item">
                <a href="/logout" class="btn btn-outline-light btn-sm">Logout</a>
            </li>
        </ul>
    </div>
</nav>

