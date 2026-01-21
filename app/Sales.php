<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Wilayah; 
use App\User;
use App\SalesOrder;
use App\TargetSales;

class Sales extends Model
{
    protected $table = 'sales';

    protected $fillable = [
        'kode_sales',
        'user_id',
        'nama_sales',
        'wilayah_id',
        'target_penjualan'
    ];

    public function wilayah()
    {
        return $this->belongsTo(Wilayah::class, 'wilayah_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function salesOrder()
    {
        return $this->hasMany(SalesOrder::class);
    }

    public function targetSales()
    {
        return $this->hasMany(TargetSales::class);
    }
}
