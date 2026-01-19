<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SalesOrder extends Model
{
    protected $table = 'sales_orders';

    protected $fillable = [
        'nomor_so',
        'tanggal',
        'sales_id',
        'customer_id',
        'total_bruto',
        'diskon_global_pct',
        'diskon_global_rp',
        'ppn_rp',
        'total_harga',
        'sisa_tagihan',
        'status',
        'tanggal_pengiriman',
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

    // sekarang produk diakses lewat details
    public function details()
    {
        return $this->hasMany(SalesOrderDetail::class, 'sales_order_id');
    }

    public function pembayaran()
    {
        return $this->hasMany(Pembayaran::class, 'sales_order_id');
    }

    public function penjualan()
    {
        return $this->hasOne(Penjualan::class, 'sales_order_id');
    }

    protected static function booted()
    {
        static::created(function ($salesOrder) {
            $customer = Customer::find($salesOrder->customer_id);
            if ($customer && $customer->status_customer === 'baru') {
                $jumlahSO = SalesOrder::where('customer_id', $customer->id)->count();
                if ($jumlahSO >= 1) {
                    $customer->update(['status_customer' => 'lama']);
                }
            }
        });
    }
}
