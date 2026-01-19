<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class SalesOrderDetail extends Model
{
    protected $table = 'sales_order_details';

    protected $fillable = [
        'sales_order_id',
        'product_id',
        'product_name',
        'qty',
        'harga_satuan',
        'subtotal',
        'note',
    ];

    public function order()
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'product_id');
    }
}
