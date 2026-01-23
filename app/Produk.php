<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Produk extends Model
{
     // Nama tabel di database
    protected $table = 'produk';

    // Kolom yang boleh diisi (mass assignment)
    protected $fillable = [
        'nama_produk',
        'kode_produk',
        'harga',
        'satuan',
    ];
}
