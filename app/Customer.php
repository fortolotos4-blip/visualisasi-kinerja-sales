<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    //
    protected $fillable = ['user_id','nama_customer', 'alamat', 'telepon', 'status_customer'];

    public function user()
{
    return $this->belongsTo(User::class);
}

}
