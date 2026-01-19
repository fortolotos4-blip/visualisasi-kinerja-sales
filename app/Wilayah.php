<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Wilayah extends Model
{
    //
    protected $table = 'wilayah';
    protected $fillable = ['kode_wilayah', 'nama_wilayah'];
}
