<?php

namespace App;
use App\Sales;
use Illuminate\Database\Eloquent\Model;

class TargetSales extends Model
{
    protected $table = 'target_sales';
    protected $fillable = [
        'sales_id','tahun','bulan','target','source','level_when_set','overridden_by','overridden_at','status'
    ];

    public function sales()
    {
        return $this->belongsTo(\App\Sales::class, 'sales_id');
    }

    protected static function booted()
{
    static::deleting(function ($targetSales) {
        // Update target_penjualan di tabel sales menjadi 0
        Sales::where('id', $targetSales->sales_id)->update(['target_penjualan' => 0]);
    });
}
}
