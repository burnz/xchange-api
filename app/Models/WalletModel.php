<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class WalletModel extends Model
{
	protected $table = 'wallet';

	protected $fillable = [
		'id',
		'user_id',
		'address',
		'coin',
		'coin_type',
		'qr_code_url',
		'balance',
		'unit',
		'locked_bal',
		'created_at',
		'updated_at'
    ];
}