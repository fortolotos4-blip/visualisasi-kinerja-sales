<nav class="navbar navbar-expand-lg navbar-dark bg-dark" role="navigation">

    {{-- BRAND --}}
    <a class="navbar-brand" href="
        @if(Auth::user()->jabatan === 'admin') {{ route('dashboard.admin') }}
        @elseif(Auth::user()->jabatan === 'manajer') {{ route('dashboard.manager') }}
        @else {{ route('dashboard.sales') }}
        @endif
    ">
        Sistem Sales
    </a>

    {{-- TOGGLER (MOBILE) --}}
    <button class="navbar-toggler" type="button"
            data-toggle="collapse"
            data-target="#navbarMain"
            aria-controls="navbarMain"
            aria-expanded="false"
            aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarMain">

        {{-- MENU KIRI --}}
        <ul class="navbar-nav mr-auto">

            {{-- ================= ADMIN ================= --}}
            @if(Auth::user()->jabatan === 'admin')

                <li class="nav-item">
                    <a class="nav-link" href="{{ route('dashboard.admin') }}">Dashboard</a>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="adminMaster"
                       data-toggle="dropdown">
                        Menu Master
                    </a>
                    <div class="dropdown-menu">
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

            {{-- ================= MANAJER ================= --}}
            @elseif(Auth::user()->jabatan === 'manajer')

                <li class="nav-item">
                    <a class="nav-link" href="{{ route('dashboard.manager') }}">Dashboard</a>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="managerData"
                       data-toggle="dropdown">
                        Data
                    </a>
                    <div class="dropdown-menu">
                        <a class="dropdown-item" href="{{ route('master.sales.manager.index') }}">Sales</a>
                        <a class="dropdown-item" href="{{ route('customer.manager.index') }}">Customer</a>
                        <a class="dropdown-item" href="{{ route('target_sales.index') }}">Target Sales</a>
                        <a class="dropdown-item" href="{{ route('kontribusi_parameters.index') }}">Indikator</a>
                    </div>
                </li>

                <li class="nav-item"><a class="nav-link" href="{{ route('penjualan.manager') }}">Penjualan</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('laporan.manager') }}">Laporan</a></li>

            {{-- ================= SALES ================= --}}
            @elseif(Auth::user()->jabatan === 'sales')

                @php
                    $sales = Auth::user()->sales ?? null;
                    $isPendingSales = $sales && is_null($sales->wilayah_id);
                @endphp

                <li class="nav-item">
                    <a class="nav-link" href="{{ route('dashboard.sales') }}">Dashboard</a>
                </li>

                @php
                    $salesMenus = [
                        'Customer'   => 'customer.index',
                        'Penawaran'  => 'penawaran.sales.index',
                        'Kunjungan'  => 'kunjungan.index',
                        'Sales Order'=> 'sales-order.my',
                        'Pembayaran' => 'pembayaran.index',
                        'Laporan'    => 'laporan.sales',
                    ];
                @endphp

                @foreach($salesMenus as $label => $route)
                    <li class="nav-item">
                        <a class="nav-link {{ $isPendingSales ? 'disabled text-muted pe-none' : '' }}"
                           href="{{ $isPendingSales ? '#' : route($route) }}">
                            {{ $label }}
                        </a>
                    </li>
                @endforeach

            @endif
        </ul>

        {{-- MENU KANAN --}}
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
