<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Pembayaran extends Model
{
    protected $table = 'pembayaran';

    protected $fillable = [
        'sales_order_id',
        'tanggal_pembayaran',
        'jumlah',
        'metode_pembayaran',
        'bukti',
        'status',
        'catatan',
    ];

    // Relasi ke Sales Order
    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    public function pembayaran()
{
    return $this->hasMany(Pembayaran::class);
}

}
