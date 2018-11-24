<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ForgotPasswordModel extends Model
{
	protected $table = 'forgot_pass';

	public $timestamps = false;

	protected $fillable = [
		'id',
		'email',
		'token',
		'created_at',
		'status'
    ];
}

