<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;

class UserModel extends Authenticatable
{
	protected $table = 'users';

	protected $fillable = [
		'customer_id',
		'first_name',
		'last_name',
		'mobile',
		'dial_code',
		'email', 
		'password',
		'country',
		'referal_code',
		'profile',
		'aml',
		'agreement',
		'email_verified',
		'mobile_verified',
		'status',
		'terms_conditions',
		'token',
		'remember_token',
		'google_auth_code',
        'auth_enabled',
       	'created_at',
       	'updated_at',
       	'auth_code_url'
    ];
}