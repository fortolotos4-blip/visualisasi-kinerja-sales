<?php

// app/Models/Penawaran.php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Sales;
use App\Customer;
use App\Produk;

class Penawaran extends Model
{
    protected $table = 'penawaran';

    protected $fillable = [
        'nomor_penawaran',
        'sales_id',
        'customer_id',
        'total_bruto',
        'diskon_global_pct',
        'diskon_global_rp',
        'ppn_rp',
        'total_harga',
        'tanggal_penawaran',
        'status',
        'keterangan',
    ];

    public function sales()
{
    return $this->belongsTo(Sales::class, 'sales_id');
}

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class);
    }

     public function details()
    {
        return $this->hasMany(PenawaranDetail::class, 'penawaran_id');
    }
}