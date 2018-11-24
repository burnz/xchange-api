<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class AddressLTCModel extends Model
{
    protected $table = 'pre_address_ltc';

    public $timestamps = false;

    protected $fillable = [
        'id','address_id', 'address', 'status','response', 'status','created_at'
    ];
}