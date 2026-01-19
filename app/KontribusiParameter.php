<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class KontribusiParameter extends Model
{
    protected $table = 'kontribusi_parameters';

    protected $fillable = [
        'manager_id',
        'periode_bulan',
        'periode_tahun',
        'bobot_kunjungan',
        'bobot_penawaran',
        'bobot_penjualan',
        'target_kunjungan',
        'target_penawaran',
        'target_penjualan',
        'status'
    ];
}
