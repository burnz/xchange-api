<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('v1/wallet/create', 'BitgoController@create_wallet');

Route::get('v1/token/{token}/list/coins', 'UserController@list_coin');

Route::get('v1/token/{token}/wallet/coins', 'UserController@user_wallet_list');

Route::get('v1/token/{token}/wallet/txn', 'UserController@user_wallet_txn');

Route::get('v1/{token}/verify/email', 'UserController@set_email_verify');

Route::post('v1/wallet/set_eth_address', 'BitgoController@set_eth_address');

Route::post('v1/user/create', 'BitgoController@create_wallet');

Route::post('v1/user/wallet', 'UserController@user_wallet_address');

Route::post('v1/user/set_address', 'UserController@set_user_address');

Route::post('v1/user/wallet/callback', 'UserController@wallet_call_back');

Route::post('v1/user/wallet/send', 'UserController@wallet_send_txn');

Route::post('v1/user/register', 'Auth\RegisterController@register');

Route::post('v1/user/login', 'Auth\LoginController@login');

Route::post('v1/password/reset', 'Auth\ResetPasswordController@reset');

Route::post('v1/password/forgot', 'Auth\ForgotPasswordController@forgot');

Route::post('v1/password/set', 'Auth\ForgotPasswordController@set');

Route::get('v1/2fa/{token}', 'UserController@two_fa_get');

Route::post('v1/2fa/set', 'UserController@two_fa_set');

Route::post('v1/set/fiat', 'UserController@set_fiat_address');

Route::post('v1/2fa/disable', 'UserController@two_fa_disable');

Route::post('v1/2fa/verify', 'Auth\RegisterController@verify2fa');

Route::get('v1/wallet/map_eth_address', 'BitgoController@mapEtherAddress');

Route::post('v1/2fa/verifycode2fa', 'UserController@verifycode2fa');
