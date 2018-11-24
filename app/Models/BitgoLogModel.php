<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Ixudra\Curl\Facades\Curl;



class BitgoLogModel extends Model
{
    protected $table = 'bitgo_response';

    public $timestamps = false;

    protected $fillable = [
        'id','response', 'request', 'url', 'created_at','event','ip_address', 'method'
    ];
}