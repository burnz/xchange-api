<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class CryptoModel extends Model
{
    protected $table = 'crypto_info';

    public $timestamps = false;

    protected $fillable = [
        'id','name', 'abbr', 'logo','rate','status'
    ];
}