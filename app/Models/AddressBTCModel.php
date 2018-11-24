<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class AddressBTCModel extends Model
{
    protected $table = 'pre_address_btc';

    public $timestamps = false;

    protected $fillable = [
        'id','address_id', 'address', 'status','response', 'status','created_at'
    ];
}