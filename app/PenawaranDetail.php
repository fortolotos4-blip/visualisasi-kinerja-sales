<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class PenawaranDetail extends Model
{
    protected $table = 'penawaran_details';
    protected $fillable = [
        'penawaran_id',
        'product_id',
        'product_name',
        'qty',
        'satuan',
        'harga_pabrik',
        'harga_kesepakatan',
        'subtotal',
        'alasan',
    ];

    public function penawaran()
    {
        return $this->belongsTo(Penawaran::class, 'penawaran_id');
    }
}
