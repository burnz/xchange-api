<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\AddressETHModel;
use App\Models\AddressBTCModel;
use App\Models\AddressLTCModel;
use App\Models\AddressBCHModel;
use App\Models\AddressXRPModel;
use App\Traits\StatusResponse;
use App\Models\UserTxnModel;
use App\Models\CryptoModel;
use App\Models\UserModel;
use App\Models\WalletModel;
use App\Libraries\LogEvent;
use App\Libraries\Bitgo;
use Illuminate\Support\Facades\Mail;
use PragmaRX\Google2FA\Google2FA;
use Validator;
use App\Libraries\Common;
class UserController extends Controller {

	use StatusResponse;

	public function __construct()
    {
        $this->obj 			= new Bitgo;
        $this->enterprise 	= env('BITGO_ENTERPRISE_ID'); 
        $this->callback 	= env('BITGO_CALL_BACK');
        $this->coins 		= ['btc','eth','ltc','bch','xrp', 'gix'];
        $this->google2fa    = new Google2FA();
        $this->acc_num      = env('BANK_ACC_NUM');
        $this->acc_ifsc     = env('BANK_ACC_IFSC');
        $this->acc_name     = env('BANK_ACC_NAME');
        $this->comm         = new Common;
    }

    public function two_fa_get($token)
    {
        $d = ['token'=>$token];

        $validator = Validator::make($d,[
            'token' => 'required|string'
        ]);

        if ($validator->fails()){
            $data = $validator->getMessageBag()->toArray();
            $message = $validator->errors()->first();
            return $this->_status('VER', $message, $data);
        } 

        $user = UserModel::where('token', $d['token'])->select('google_auth_code','auth_code_url')->first();
        if($user == '')
            return $this->_status('ERR', 'User does not exist');

        $response = ['secret_key'=>$user->google_auth_code,'qr_code'=>$user->auth_code_url];

        return $this->_status('SUCC','User 2FA',$response);    
    }

    public function two_fa_set(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'token'      => 'required|string',
            'auth_code'  => 'required|numeric|min:6'
        ]);

        if ($validator->fails()){
            $data = $validator->getMessageBag()->toArray();
            $message = $validator->errors()->first();
            return $this->_status('VER', $message, $data);
        }

        $d = $request->all();

        $user = UserModel::where('token','=',$d['token'])->select('google_auth_code')->first();

        if($user == '')
            return $this->_status('ERR', 'user_id does not exist');

        try {

            $valid = $this->google2fa->verifyKey($user->google_auth_code, $d['auth_code']);

            if($valid){
                UserModel::where('token',$d['token'])->update([
                    'auth_enabled'=>1
                ]);
                return $this->_status('SUCC', "Google2FA is valid");
            }
            else
                return $this->_status('ERR', "Google2FA is not valid");
        }

        catch (\Exception $e) {
            return $this->_status('ERR', $e->getMessage());
        }        
    }

    public function two_fa_disable(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'token'      => 'required|string',
            'auth_code'  => 'required|numeric|min:6'
        ]);

        if ($validator->fails()){
            $data = $validator->getMessageBag()->toArray();
            $message = $validator->errors()->first();
            return $this->_status('VER', $message, $data);
        }

        $d = $request->all();

        $user = UserModel::where('token','=',$d['token'])->select('google_auth_code')->first();

        if($user == '')
            return $this->_status('ERR', 'user_id does not exist');

        try {

            $valid = $this->google2fa->verifyKey($user->google_auth_code, $d['auth_code']);

            if($valid){
                UserModel::where('token',$d['token'])->update([
                    'auth_enabled'=>0
                ]);
                return $this->_status('SUCC', "Google2FA is Disable");
            }
            else
                return $this->_status('ERR', "Google2FA is not valid");
        }

        catch (\Exception $e) {
            return $this->_status('ERR', $e->getMessage());
        }
    }

    public function set_email_verify($token)
    {
       $d = ['token'=>$token];

        $validator = Validator::make($d,[
            'token' => 'required|string'
        ]);

        if ($validator->fails()){
            $data = $validator->getMessageBag()->toArray();
            $message = $validator->errors()->first();
            return $this->_status('VER', $message, $data);
        }

        $user = UserModel::where('token',$d['token'])->select('id','customer_id')->first();
        if($user == '')
            return $this->_status('ERR','Invalid User Token');

        UserModel::where('token',$d['token'])->update(['email_verified'=>1]);

        return $this->_status('SUCC','Email has been verified');
    }

    public function set_fiat_address(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'token' => 'required|string'
        ]);

        if ($validator->fails()){
            $data = $validator->getMessageBag()->toArray();
            $message = $validator->errors()->first();
            return $this->_status('VER', $message, $data);
        }

        $d = $request->all();

        $user = UserModel::where('token',$d['token'])->select('id','customer_id')->first();
        if($user == '')
            return $this->_status('ERR','Invalid User Token');

        $wallet = WalletModel::where(['coin'=>'inr', 'user_id'=>$user->id])->select('address','coin','qr_code_url','balance')->first();
        if($wallet == ''){
           $wallet['inr'] = [
                'user_id'=> $user->id,
                'address'=> $this->acc_num,
                'coin'   => 'inr',
                'coin_type'=>'fiat',
                'qr_code_url' => 'https://chart.googleapis.com/chart?cht=qr&chs=300x300&chl='.$this->acc_num.'&choe=UTF-8',
                'balance'=> 0,
                'locked_bal'=>0
            ];

            WalletModel::create($wallet['inr']);

            $gix = [
                'user_id'=> $user->id,
                'address'=> time(),
                'coin'   => 'gix',
                'coin_type'=>'crypto',
                'qr_code_url' => 'https://chart.googleapis.com/chart?cht=qr&chs=300x300&chl='.time().'&choe=UTF-8',
                'balance'=> 0,
                'locked_bal'=>0
            ];

            WalletModel::create($gix);


            $wallet['inr']['ifsc'] = $this->acc_ifsc;
            $wallet['inr']['name'] = $this->acc_name;

            return $this->_status('SUCC', 'Address has been set', $wallet['inr']);

        }else
            return $this->_status('ERR', 'Address has already set');
    }

    public function list_coin($token)
    {
    	$d = ['token'=>$token];

    	$validator = Validator::make($d,[
            'token' => 'required|string'
        ]);

        if ($validator->fails()){
            $data = $validator->getMessageBag()->toArray();
            $message = $validator->errors()->first();
            return $this->_status('VER', $message, $data);
        }

        $user = UserModel::where('token',$d['token'])->select('id','customer_id')->first();
        if($user == '')
            return $this->_status('ERR','Invalid User Token');

    	$coin  = $this->get_currency_list($this->coins);

    	if(isset($coin['status']))
			return $this->_status('ERR', 'No coin exist');

    	$event = [
            'user_id' => $user->id,
            'user_ip' => \Request::ip(),
            'event'	  => 'GET COIN LIST'
        ];

        LogEvent::addEvent($event);

    	return $this->_status('SUCC', 'COIN LIST', $coin);
    }

    public function user_wallet_list($token)
    {
    	$d = ['token'=>$token];

    	$validator = Validator::make($d,[
            'token' => 'required|string'
        ]);

        if ($validator->fails()){
            $data = $validator->getMessageBag()->toArray();
            $message = $validator->errors()->first();
            return $this->_status('VER', $message, $data);
        }

        $user = UserModel::where('token',$d['token'])->select('id','customer_id')->first();

        if($user == '')
            return $this->_status('ERR','Invalid User Token');

        $coin 	= $this->get_currency_list($this->coins);

        if(isset($coin['status']))
			return $this->_status('ERR', 'No coin exist'); 

        $user_wallet = [];

        foreach ($coin as $key => $value) {

        	$wallet = WalletModel::where(['coin'=>$key, 'user_id'=>$user->id])->select('address','coin','qr_code_url','balance','locked_bal')->first();

        	if($wallet == '')
        		$user_wallet[$key] = ['status' => 0];
        	else{

        		$user_wallet[$key] = [
        			'status' => 1,
        			'address'=> $wallet->address,
        			'coin'	 => $wallet->coin,
                    'in_order'=>$this->cal_bal_unit($key,$wallet->locked_bal), 
        			'qr_code_url' => $wallet->qr_code_url,
        			'balance'=> $this->cal_bal_unit($key,$wallet->balance)
        		];
        	} 
        }

        $event = [
            'user_id' => $user->id,
            'user_ip' => \Request::ip(),
            'event'	  => 'USER WALLET LIST'
        ];

        LogEvent::addEvent($event);		
        return $this->_status('SUCC','User Wallet List',$user_wallet);
    }

    public function set_user_address(Request $request)
    {
    	$validator = Validator::make($request->all(),[
            'token' => 'required|string',
            'coin'  => 'required|in:eth,btc,xrp,bch,ltc|min:3'
        ]);

        if ($validator->fails()){
            $data = $validator->getMessageBag()->toArray();
            $message = $validator->errors()->first();
            return $this->_status('VER', $message, $data);
        }

        $d    = $request->all();
        $user = UserModel::where('token',$d['token'])->select('id','customer_id')->first();
        if($user == '')
            return $this->_status('ERR','Invalid User Token');

        //Check user address exist or not
        $coin = $this->get_currency_list([$d['coin']]);

        if(isset($coin['status']))
			return $this->_status('ERR', 'No coin exist');

        $wallet = WalletModel::where(['coin'=>$d['coin'], 'user_id'=>$user->id])->select('address','coin','qr_code_url','balance', 'locked_bal')->first();

        if($wallet == ''){
            $address = $this->assign_address($user->id, $d['coin']);
        }else
            return $this->_status('ERR', 'Address has already set');

        $event = [
            'user_id' => $user->id,
            'user_ip' => \Request::ip(),
            'event'	  => 'SET USER ADDRESS',
            'data'	  => json_encode($d)
        ];

        LogEvent::addEvent($event);		
        return $this->_status('SUCC','User Wallet List',$address);
    }

    public function wallet_call_back(Request $request)
    {
        $d = $request->all();
        $log = $this->obj->bit_log_call_back($d);

        $p = [

            'coin'      => $d['coin'],
            'transfer'  => $d['transfer']
        ];

        $response = $this->obj->bit_get_txn_tansfer_id($p);
        
        if((isset($response['error']) && !empty($response['error'])) || $response == '')
            return $this->_status('ERR','GET_TXN_DETAIL',$response);
        
        foreach ($response['entries'] as $key => $value) {
            if ($value['value'] > 0) {
                $address = $value['address'];

                $wallet = WalletModel::where(['address'=>$address])->select('address','user_id','coin','qr_code_url','balance')->first();

                if($wallet != ''){
                    $txn  = UserTxnModel::where(['txn_id'=>$d['hash'], 'user_id'=>$wallet->user_id])->select('user_id')->first();
                    if($txn == ''){
                        UserTxnModel::create([
                            'user_id'=>$wallet->user_id,
                            'ref_id'=>date('YmdHis').$wallet->user_id,
                            'txn_type'=>'DEPOSIT',
                            'transfer_id'=>$d['transfer'],
                            'address'=>$address,
                            'wallet_id'=>$d['wallet'],
                            'state'=>$response['state'],
                            'txn_id'=>$d['hash'],
                            'currency'=>$wallet->coin,
                            'amount'=>$value['value'],
                            'confirmations'=>$response['confirmations'],
                            'createdTime'=>date('Y-m-d H:i:s'),
                            'response'=>json_encode($response)
                        ]);
                    }else{
                        
                        UserTxnModel::where('txn_id',$d['hash'])->update([
                            'state'=>$response['state'],
                            'confirmations'=>$response['confirmations']
                        ]);  
                    }

                    if($response['state'] == 'confirmed')
                    {
                        $balance = (int)$wallet->balance + (int) $value['value']; 
                        WalletModel::where(['address'=>$address])->update(['balance' => $balance]);
                        break;
                    }
                }
            }
        }
    }

    public function wallet_send_txn(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'token' => 'required|string',
            'coin'  => 'required|in:eth,btc,xrp,bch,ltc|min:3',
            'amount'=> 'required|numeric',
            'destination' => 'required|string'
        ]);

        if ($validator->fails()){
            $data = $validator->getMessageBag()->toArray();
            $message = $validator->errors()->first();
            return $this->_status('VER', $message, $data);
        }

        $d    = $request->all();

        $coin   = $this->get_currency_list([$d['coin']]);

        if(isset($coin['status']))
            return $this->_status('ERR', 'No coin exist');

        $user = UserModel::where('token',$d['token'])->select('id','customer_id','auth_enabled')->first();
        if($user == '')
            return $this->_status('ERR','Invalid User Token');
        $wallet = WalletModel::where(['user_id'=>$user->id,'coin'=>$d['coin']])->select('balance')->first();
        if($wallet == '')
            return $this->_status('ERR','Coin does not exist for user');

        $balance = $wallet->balance;

        $amount  = $this->convert_unit($d['coin'], $d['amount']);

        $suff_bal= $this->cal_suff_bal($amount,$balance,$d['coin']);

        if(isset($suff_bal['status']) && $suff_bal['status'] == 'error')
            return $this->_status('ERR',$suff_bal['message']);

        $txn = [
            'user_id'   => $user->id,
            'ref_id'    => date('YmdHis').$user->id,
            'txn_type'  => 'WITHDRAW',
            'wallet_id' => $this->obj->get_wallet_id($d['coin']),
            'address'   => $d['destination'],
            'currency'  => $d['coin'],
            'amount'    => $amount,
            'createdTime'=>date('Y-m-d H:i:s'),
            'state'     => 'PENDING'
        ];

        $txn = UserTxnModel::create($txn);
        $txn_id = $txn->id;

        if($user->auth_enabled == 1)
        {
            if(!isset($d['secret']) || is_null($d['secret']))
                return $this->_status('VER','Please enter OTP.');
            $v = [

                'auth_code' => $d['secret'],
                'user_id'   => $user->id
            ];

            $verify = $this->comm->verify_2fa($v);

            if($verify['status'] == 'error')
                return $this->_status('ERR',$verify['error']);
        }


        $p = [
            'coin'       => $d['coin'],
            'destination'=> $d['destination'],
            'amount'     => $amount
        ];

        $response = $this->obj->bit_send_txn($p);

        if(isset($response['error']) && !empty($response['error']))
        {
            UserTxnModel::where('id',$txn_id)->update(['state'=>'FAILED','response'=>json_encode($response)]);
            return $this->_status('ERR','Transaction Has been failed',$response);
        }

        WalletModel::where(['user_id'=>$user->id,'coin'=>$d['coin']])->update(['balance'=>$suff_bal]);

        UserTxnModel::where('id',$txn_id)->update(['state'=>'confirmed','txn_id'=>$response['txid'],'response'=>json_encode($response)]);

        return $this->_status('SUCC','Transaction Has been done successfully',$response);
    }

    public function user_wallet_txn($token)
    {
        $d = ['token'=>$token];

        $validator = Validator::make($d,[
            'token' => 'required|string'
        ]);

        if ($validator->fails()){
            $data = $validator->getMessageBag()->toArray();
            $message = $validator->errors()->first();
            return $this->_status('VER', $message, $data);
        }

        $user = UserModel::where('token',$d['token'])->select('id','customer_id')->first();

        if($user == '')
            return $this->_status('ERR','Invalid User Token');

        $txn = UserTxnModel::where('user_id',$user->id)->select('currency','ref_id','txn_type','address','txn_id')->orderBy('id', 'desc')->get();
        //dd($txn);
        $list = [];
        $i = 0;
        $j = 0;
        $k = 0;
        $l = 0;
        $m = 0; 
        foreach ($txn as $key => $value) {
            if($value->currency == 'eth')
            {
                $list[$value->currency][$i]['ref_id'] = $value->ref_id;
                $list[$value->currency][$i]['txn_type'] = $value->txn_type;
                $list[$value->currency][$i]['address']  = $value->address;
                $list[$value->currency][$i]['txn_id']   = $value->txn_id;
                $i++;

            }elseif($value->currency == 'btc'){
                $list[$value->currency][$j]['ref_id'] = $value->ref_id;
                $list[$value->currency][$j]['txn_type'] = $value->txn_type;
                $list[$value->currency][$j]['address']  = $value->address;
                $list[$value->currency][$j]['txn_id']   = $value->txn_id;
                $j++;
            }elseif($value->currency == 'ltc'){
                $list[$value->currency][$k]['ref_id'] = $value->ref_id;
                $list[$value->currency][$k]['txn_type'] = $value->txn_type;
                $list[$value->currency][$k]['address']  = $value->address;
                $list[$value->currency][$k]['txn_id']   = $value->txn_id;
                $k++;
            }elseif($value->currency == 'bch'){
                $list[$value->currency][$l]['ref_id'] = $value->ref_id;
                $list[$value->currency][$l]['txn_type'] = $value->txn_type;
                $list[$value->currency][$l]['address']  = $value->address;
                $list[$value->currency][$l]['txn_id']   = $value->txn_id;
                $l++;
            }elseif($value->currency == 'xrp'){
                $list[$value->currency][$m]['ref_id']   = $value->ref_id;
                $list[$value->currency][$m]['txn_type'] = $value->txn_type;
                $list[$value->currency][$m]['address']  = $value->address;
                $list[$value->currency][$m]['txn_id']   = $value->txn_id;
                $m++;
            }
        }
        return $this->_status('SUCC','User Txn history',$list);
    }

    private function cal_bal_unit($coin,$amount)
    {
        $balance = (double) $amount;
        if($coin == 'eth' || $coin == 'gix')
            $balance = $balance/pow(10,18);
        elseif($coin == 'ltc' || $coin == 'btc' || $coin == 'bch')
            $balance = $balance/pow(10,8);
        elseif($coin == 'xrp')
           $balance = $balance/pow(10,6);
        return $balance;
    }

    private function convert_unit($coin,$amount)
    {
        $balance = (double) $amount;
        if($coin == 'eth')
            $balance = $balance*pow(10,18);
        elseif($coin == 'ltc' || $coin == 'btc' || $coin == 'bch')
            $balance = $balance*pow(10,8);
        elseif($coin == 'xrp')
            $balance = $balance*pow(10,6);
        return (string)((int)$balance);
    }

    private function cal_suff_bal($amount,$balance,$coin)
    {
        $wallet = CryptoModel::where(['abbr'=>$coin])->select('fees')->first();
        if($wallet == '')
            return ['status'=>'error', 'message'=>'Coin crypto does not exist'];
         $total_amount_send = $amount + $wallet->fees;
        if($balance <= $total_amount_send)
            return ['status'=>'error', 'message'=>'Balance is insufficient'];
        $remain_balance = $balance - $total_amount_send;
        return $remain_balance;
    }

    private function get_currency_list(array $data)
    {
    	$coin = CryptoModel::whereIn('abbr', $data)->where('status',1)->select('name','abbr','logo','fees')->orderBy('id', 'asc')->get();
    	if(count($coin) == 0 || $coin == '')
    		return ['status'=>'error'];

    	$list = []; 
    	
    	foreach ($coin as $key => $value) {
    		$list[$value->abbr]['name'] = $value->name;
    		$list[$value->abbr]['abbr'] = $value->abbr;
    		$list[$value->abbr]['logo'] = $value->logo;
    		$list[$value->abbr]['fees'] = $value->fees;
    	}
    	
    	return $list;	
    }

    private function assign_address($user_id,$coin)
    {
    	$wallet = []; 

    	if($coin == 'btc'){

    		$address = AddressBTCModel::where('status',1)->select('address')->orderBy('id', 'asc')->first();
            if($address == '')
                return $this->_status('ERR', 'No addresses are available');

            AddressBTCModel::where('address',$address->address)->update(['status'=>0]);
    	}elseif($coin == 'eth'){
    		$address = AddressETHModel::where('status',1)->select('address')->orderBy('id', 'asc')->first();
            if($address == '')
                return $this->_status('ERR', 'No addresses are available');

            AddressETHModel::where('address',$address->address)->update(['status'=>0]);
    	}elseif($coin == 'xrp'){
    		$address = AddressXRPModel::where('status',1)->select('address')->orderBy('id', 'asc')->first();
            if($address == '')
                return $this->_status('ERR', 'No addresses are available');

            AddressXRPModel::where('address',$address->address)->update(['status'=>0]);
    	}elseif($coin == 'bch'){
    		$address = AddressBCHModel::where('status',1)->select('address')->orderBy('id', 'asc')->first();
            if($address == '')
                return $this->_status('ERR', 'No addresses are available');

            AddressBCHModel::where('address',$address->address)->update(['status'=>0]);
    	}elseif($coin == 'ltc'){
    		$address = AddressLTCModel::where('status',1)->select('address')->orderBy('id', 'asc')->first();
            if($address == '')
                return $this->_status('ERR', 'No addresses are available');

            AddressLTCModel::where('address',$address->address)->update(['status'=>0]);
    	}

    	$wallet[$coin] = [
    		'user_id'=> $user_id,
			'address'=> $address->address,
			'coin'	 => $coin,
            'coin_type'=>'crypto',
			'qr_code_url' => 'https://chart.googleapis.com/chart?cht=qr&chs=300x300&chl='.$address->address.'&choe=UTF-8',
			'balance'=> 0,
            'in_order'=>0
		];
		WalletModel::create($wallet[$coin]);
		return $wallet[$coin];
    }
	public function verifycode2fa(Request $request)
	{
		$d = $request->all();		
		$user = UserModel::where('token',$d['token'])->select('id','customer_id','auth_enabled')->first();		
		$v = [

                'auth_code' => $d['secret'],
                'user_id'   => $user->id 
            ];
        $verify = $this->comm->verify_2fa($v);

		if($verify['status'] == 'error')
                return $this->_status('ERR2FA',$verify['error']);			
			
		if($verify['status'] == 'success')
			return $this->_status('SUCC',$verify['message']);
		
	}

}