<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class AddressBCHModel extends Model
{
    protected $table = 'pre_address_bch';

    public $timestamps = false;

    protected $fillable = [
        'id','address_id', 'address', 'status','response', 'status','created_at'
    ];
}