@extends('layouts.app')
@include('layouts.navbar')

@section('content')
<div class="container mt-4">
    <h4>Tambah Target Penjualan (Per Bulan)</h4>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('target_sales.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label>Tahun</label>
                    <input type="number" name="tahun" class="form-control"
                        value="{{ date('Y') }}" min="2020" required>
                </div>

                <div class="mb-3">
                    <label>Bulan</label>
                    <select name="bulan" class="form-control" required>
                        @foreach(range(1,12) as $b)
                            <option value="{{ $b }}">{{ date('F', mktime(0,0,0,$b,1)) }}</option>
                        @endforeach
                    </select>
                </div>

                <hr>

                <h5>Default Target Berdasarkan Level</h5>

                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th style="width:200px">Level</th>
                                <th>Target (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($levels as $lvl)
                            <tr>
                                <td>{{ $lvl->level }}</td>
                                <td>
                                    <input type="number" name="default_target[{{ $lvl->level }}]"
                                           class="form-control"
                                           value="{{ $lvl->amount }}"
                                           min="0" required>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="{{ route('target_sales.index') }}" class="btn btn-secondary">Kembali</a>
            </form>
        </div>
    </div>
</div>
@endsection
