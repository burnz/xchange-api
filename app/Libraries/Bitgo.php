<?php
namespace App\Libraries;
use DB;
use App\Models\BitgoLogModel;
use Ixudra\Curl\Facades\Curl;
use App\Traits\StatusResponse;

class Bitgo{

    use StatusResponse;

	public function __construct()
    {
    	$this->local_v2_url = env('BITGO_V2_LOCAL_URL');
    	$this->token 		= env('BITGO_TOKEN');
        $this->bitgo_url    = env('BITGO_URL');
        $this->enterprise   = env('BITGO_ENTERPRISE_ID');

        $this->btc_wallet   = env('BITGO_BTC_WALLET_ID');
        $this->ltc_wallet   = env('BITGO_LTC_WALLET_ID');
        $this->bch_wallet   = env('BITGO_BCH_WALLET_ID');
        $this->xrp_wallet   = env('BITGO_XRP_WALLET_ID');
        $this->eth_wallet   = env('BITGO_ETH_WALLET_ID');

        $this->mode = env('MODE');

        $this->google2fa   = new \Google\Authenticator\GoogleAuthenticator();

        $this->auth_secret = env('GOOGLE_AUTH_SECRET');
    }

    public function bit_create_wallet_post(array $data)
    {
    	$wal = $this->get_wallet_id($data['coin']); 
        $url = $this->bitgo_url.$data['coin'].'/wallet/'.$wal.'/address';

    	$response = Curl::to($url)
            ->withHeader("Authorization: Bearer $this->token")
            ->withContentType("application/json")
            ->post();

        $resp = BitgoLogModel::create([
            'event'     => 'BITGO_CREATE_WALLET',
            'url'       => $url,
            'method'    => 'POST',
            'request'   => '',
            'response'  => $response,
            'ip_address'=> request()->ip(),
            'created_at'=> date('Y-m-d H:i:s'),
        ]);

        return json_decode($response,true);  
    }

    public function bit_wallet_address_get(array $data) //For eth
    {
        $wal = $this->get_wallet_id($data['coin']); 
        $url = $this->bitgo_url.$data['coin'].'/wallet/'.$wal.'/address/'.$data['address_id'];
        $response = Curl::to($url)
            ->withHeader("Authorization: Bearer $this->token")
            ->withContentType("application/json")
            ->get();
        $resp = BitgoLogModel::create([
            'event'     => 'BITGO_GET_ADDRESS',
            'url'       => $url,
            'method'    => 'GET',
            'request'   => '',
            'response'  => $response,
            'ip_address'=> request()->ip(),
            'created_at'=> date('Y-m-d H:i:s'),
        ]);

        return json_decode($response,true);
    }

    public function bit_send_txn(array $data)
    {
        $coin  = $this->get_coin($this->mode, $data['coin']);
        $wal   = $this->get_wallet_id($coin); 
        $url   = $this->local_v2_url.'/'.$coin.'/wallet/'.$wal.'/sendcoins';

        $d     = [
            'url' => $url,
            'req' => [
                'walletPassphrase' => $this->get_wallet_pass($coin),
                'address' => $data['destination'],
                'amount'  => $data['amount']
            ],
        ]; 

        $response = $this->send_txn($d);

        if(isset($response['error']) && $response['error'] == 'needs unlock'){
            try {

                $code = $this->google2fa->getCode($this->auth_secret);
                $unlock = $this->set_unlock($code);

                if((isset($unlock['error']) && !empty($unlock['error'])) || $unlock == '')
                    return $unlock;
                else{

                    $response = $this->send_txn($d);

                    return $response;
                }
            }catch (\Exception $e) {
                return ['error' => $e->getMessage()];
            }
        }else{

            return $response;
        }
    }

    public function bit_log_call_back(array $data)
    {
        $resp = BitgoLogModel::create([
            'event'     => 'BITGO_CALLBACK',
            'url'       => '',
            'method'    => 'POST',
            'request'   => '',
            'response'  => json_encode($data),
            'ip_address'=> request()->ip(),
            'created_at'=> date('Y-m-d H:i:s'),
        ]);  
    }

    public function bit_get_wallet_txn(array $data)
    {
        $wal = $this->get_wallet_id($data['coin']); 
        $url = $this->bitgo_url.$data['coin'].'/wallet/'.$wal.'/tx/'.$data['txn'];

        $response = Curl::to($url)
            ->withHeader("Authorization: Bearer $this->token")
            ->withContentType("application/json")
            ->get();

        $resp = BitgoLogModel::create([
            'event'     => 'BITGO_GET_TXN',
            'url'       => $url,
            'method'    => 'POST',
            'request'   => json_encode($data),
            'response'  => $response,
            'ip_address'=> request()->ip(),
            'created_at'=> date('Y-m-d H:i:s'),
        ]);

        return json_decode($response,true); 
    }

    public function bit_get_txn_tansfer_id(array $data)
    {
        $wal = $this->get_wallet_id($data['coin']); 
        $url = $this->bitgo_url.$data['coin'].'/wallet/'.$wal.'/transfer/'.$data['transfer'];

        $response = Curl::to($url)
            ->withHeader("Authorization: Bearer $this->token")
            ->withContentType("application/json")
            ->get();

        $resp = BitgoLogModel::create([
            'event'     => 'BITGO_GET_TXN',
            'url'       => $url,
            'method'    => 'POST',
            'request'   => json_encode($data),
            'response'  => $response,
            'ip_address'=> request()->ip(),
            'created_at'=> date('Y-m-d H:i:s'),
        ]);

        return json_decode($response,true);
    }

    public function get_wallet_id($coin)
    {
    	if($coin == 'btc' || $coin == 'tbtc')
            $w = $this->btc_wallet;
		elseif($coin == 'eth' || $coin == 'teth')
			$w = $this->eth_wallet;
        elseif($coin == 'ltc' || $coin == 'tltc')
            $w = $this->ltc_wallet;
        elseif($coin == 'bch' || $coin == 'tbch')
           	$w = $this->bch_wallet;
        elseif($coin == 'xrp' || $coin == 'txrp')
            $w = $this->xrp_wallet;

        return $w;
    }

    private function get_wallet_pass($coin)
    {
        if($coin == 'btc' || $coin == 'tbtc')
            $w = env('BITGO_ETH_PASS');
        elseif($coin == 'eth' || $coin == 'teth')
            $w = env('BITGO_ETH_PASS');
        elseif($coin == 'ltc' || $coin == 'tltc')
            $w = env('BITGO_ETH_PASS');
        elseif($coin == 'bch' || $coin == 'tbch')
            $w = env('BITGO_ETH_PASS');
        elseif($coin == 'xrp' || $coin == 'txrp')
            $w = env('BITGO_ETH_PASS');
        return $w;
    }

    public function get_coin($mode, $coin)
    {
        if($mode == 'TEST')
        {
            if($coin == 'btc')
                $c = 'tbtc';
            elseif($coin == 'eth')
                $c = 'teth';
            elseif($coin == 'ltc')
                $c = 'tltc';
            elseif($coin == 'bch')
                $c = 'tbch';
            elseif($coin == 'xrp')
                $c = 'txrp';
        }else{

            $c = $coin;
        }

        return $c;
    }

    private function set_unlock($code)
    {
        $url = $this->bitgo_url.'user/unlock'; 

        $d   = ['otp'=>$code, 'duration'=>'3600'];

        $response = Curl::to($url)
            ->withData(json_encode($d))
            ->withHeader("Authorization: Bearer $this->token")
            ->withContentType("application/json")
            ->post();

        $resp = BitgoLogModel::create([
            'event'     => 'BITGO_UNLOCK',
            'url'       => $url,
            'method'    => 'POST',
            'request'   => json_encode($d),
            'response'  => $response,
            'ip_address'=> request()->ip(),
            'created_at'=> date('Y-m-d H:i:s'),
        ]);

        return json_decode($response,true); 
    }

    private function send_txn(array $data) 
    {
        $response = Curl::to($data['url'])
            ->withData(json_encode($data['req']))
            ->withHeader("Authorization: Bearer $this->token")
            ->withContentType("application/json")
            ->post();
        
        $resp = BitgoLogModel::create([
            'event'     => 'BITGO_SEND_TXN',
            'url'       => $data['url'],
            'method'    => 'POST',
            'request'   => json_encode($data['req']),
            'response'  => $response,
            'ip_address'=> request()->ip(),
            'created_at'=> date('Y-m-d H:i:s'),
        ]);

        return json_decode($response,true);
    }

    public function mapEtherAddress(){

        $result = DB::table('pre_address_eth')->select(['address_id'])->where(['is_map'=>0, 'status'=>1]);
        $address_ids = $result->get()->toArray();

        $prevId = '';
        $total_map = 0;
        $coin  = $this->get_coin($this->mode, 'eth');
        if(!empty($address_ids))
        {
            $wallet_id =$this->get_wallet_id($coin);
            foreach($address_ids as $key => $value) {

                $address_id =  $value->address_id;

                for($i=1; $i<500; $i++)
                {

                    $url = $this->bitgo_url . "$coin/wallet/$wallet_id/addresses?limit=500&prevId=" . $prevId;

                    
                    $resp = Curl::to($url)
                        ->withHeader("Authorization: Bearer $this->token")
                        ->withContentType("application/json")
                        ->get();

                        $address = json_decode($resp, true);

                        if(isset($address['addresses'])) {


                            if(in_array($address_id, array_column($address['addresses'], 'id'))) {


                                $key = array_search($address_id, array_column($address['addresses'], 'id'));
                                $wallet_address = $address['addresses'][$key]['address'];


                                $resp = DB::table('pre_address_eth')->where(['address_id'=>$address_id])->update(['address'=>$wallet_address, 'is_map'=>1, 'updated_at'=>date('Y-m-d H:i:s')]);

                                if($resp) {

                                   $total_map++;
                                   break;
                                }
                            }
                            else
                            {
                                if (isset($address['nextBatchPrevId'])) {
                                    $prevId = $address['nextBatchPrevId'];
                                    continue;
                                }

                            }

                        }
                        else
                        {
                            break;
                        }
                    
                }
            }

            $this->_status('BITGOSUCC', 'MAP Pre Address generated report', ['TOTAL MAPPED'=>$total_map]);
            return $this->_status('SUCC', 'MAP Pre Address generation script completed.');
        }
        else{
            return $this->_status('SUCC', 'No pre address id found in database');
        }
    }

}