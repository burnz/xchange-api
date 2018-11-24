<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class AddressXRPModel extends Model
{
    protected $table = 'pre_address_xrp';

    public $timestamps = false;

    protected $fillable = [
        'id','address_id', 'address', 'status','response', 'status','created_at'
    ];
}