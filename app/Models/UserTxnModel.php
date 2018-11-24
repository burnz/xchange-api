<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class UserTxnModel extends Model
{
	protected $table = 'user_txns';
	public $timestamps = false;
	protected $fillable = [
		'user_id',
		'ref_id',
		'txn_type',
		'transfer_id',
		'wallet_id',
		'address',
		'txn_id',
		'currency',
		'amount',
		'createdTime',
		'state',
		'confirmations',
		'response',
    ];
}