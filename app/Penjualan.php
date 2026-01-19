<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class Penjualan extends Model
{
    //
    protected $table = 'penjualan';

    protected $fillable = [
        'sales_order_id',
        'tanggal_pelunasan',
        'sales_id',
        'customer_id',
        'total_harga',
        'nomor_faktur',
    ];

    public function sales()
    {
        return $this->belongsTo(Sales::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class);
    }
    public function salesOrder()
{
    return $this->belongsTo(SalesOrder::class, 'sales_order_id');
}

}
