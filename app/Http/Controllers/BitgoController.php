<?php

namespace App\Http\Controllers;

use App\Libraries\Bitgo;
use Illuminate\Http\Request;
use App\Models\BitgoModel;
use App\Models\AddressBCHModel;
use App\Models\AddressBTCModel;
use App\Models\AddressLTCModel;
use App\Models\AddressETHModel;
use App\Models\AddressXRPModel;
use App\Traits\StatusResponse;
use Validator;

class BitgoController extends Controller {
	
	use StatusResponse;

	public function __construct()
	{
		$this->obj 	= new Bitgo;

		$this->mode = env('MODE');

		$this->enterprise = env('BITGO_ENTERPRISE_ID');
	}

	public function create_wallet(Request $request)
	{
		$validator = Validator::make($request->all(),[
            'coin' 	=> 'required|in:eth,ltc,bch,btc,xrp|min:3',
            'number'=> 'required|integer'
        ]);

        if ($validator->fails()){

        	$data = $validator->getMessageBag()->toArray();

        	$message = $validator->errors()->first();

        	return $this->_status('VER', $message, $data);

        }else{

        	$d = $request->all();

        	$coin = $this->obj->get_coin($this->mode,$d['coin']);

        	$wallet = [];

        	for ($i = 0; $i < $d['number']; $i++) {

        		$p = [
        			'coin'=> $coin
        		];

        		$response = $this->obj->bit_create_wallet_post($p); 

        		if((isset($response['error']) && !empty($response['error'])) || $response == '')
                    return $this->_status('ERR','CREATE_ADDRESS',$response);

                $m = [
	    			'address_id'=> $response['id'],
	    			'response'	=> json_encode($response),
	    			'status'	=> 1,
	    			'created_at'=> date("Y-m-d H:i:s")
	    		];

	    		if($d['coin'] == 'eth')
	    			$model = AddressETHModel::create($m);
	    		else{

	    			$m['address'] = $response['address'];

	    			if($d['coin'] == 'xrp')
	                	$model = AddressXRPModel::create($m);

		            if($d['coin'] == 'bch')
		                $model = AddressBCHModel::create($m);

		            if($d['coin'] == 'btc')
		                $model = AddressBTCModel::create($m);

		            if($d['coin'] == 'ltc')
		                $model = AddressLTCModel::create($m);
	    		}

	            $wallet[$i] = $response['id'];
	        }


	        return $this->_status('SUCC', 'Address has been generated', $wallet);
        }
	}
	public function mapEtherAddress(){
        return $this->obj->mapEtherAddress();
    }

	public function set_eth_address(Request $request)
	{
		$validator = Validator::make($request->all(),[
            'address_id'=> 'required|array'
        ]);

        if ($validator->fails()){

        	$data = $validator->getMessageBag()->toArray();

        	$message = $validator->errors()->first();

        	return $this->_status('VER', $message, $data);

        }else{

        	$d = $request->all();

        	$coin = $this->obj->get_coin($this->mode,'eth');

        	$wallet = [];

			foreach ($d['address_id'] as $key => $value) {

				$p = [
					'address_id'=>$value,
        			'coin'=> $coin
        		];

        		$response = $this->obj->bit_wallet_address_get($p);

        		if((isset($response['error']) && !empty($response['error'])) || $response == '')
                    return $this->_status('ERR','CREATE_ADDRESS',$response);

                AddressETHModel::where(['address_id'=>$value])->update([
                	'address'=>$response['address'],
                	'response'=>json_encode($response)
                ]);

        		$wallet[$key] = $response['address'];
			}

			return $this->_status('SUCC', 'Address has been set', $wallet);
        }
	}

}