<?php

namespace App;
use App\Sales;
use App\Customer;

use Illuminate\Database\Eloquent\Model;

class KunjunganSales extends Model
{
    protected $table = 'kunjungan_sales';

    protected $fillable = [
        'sales_id', 'customer_id', 'tanggal_kunjungan', 'tujuan', 'status', 'keterangan'
    ];

    // App\KunjunganSales.php
public function sales()
{
    return $this->belongsTo(Sales::class, 'sales_id');
}


    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
