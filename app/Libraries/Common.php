<?php
namespace App\Libraries;
use DB;
use App\Models\BitgoLogModel;
use Ixudra\Curl\Facades\Curl;
use App\Traits\StatusResponse;
use PragmaRX\Google2FA\Google2FA;
use App\Models\UserModel;

class Common{

	use StatusResponse;

	public function __construct()
	{
		$this->google2fa  = new Google2FA();

        $this->google2fa->setAllowInsecureCallToGoogleApis(true);
	}

	public function verify_2fa(array $d)
	{
		
		$user = UserModel::where('id','=',$d['user_id'])->select('google_auth_code')->first();

        if($user == '')
            return $this->_status('ERR', 'user_id does not exist');

        try {
			
            $valid = $this->google2fa->verifyKey($user->google_auth_code, $d['auth_code']);

            if(!$valid)
            	return ['status'=>'error','error'=>'Google2FA is not valid'];

            else
            	return ['status'=>'success','message'=>'Google2FA is valid'];	
        }
        catch (\Exception $e) {
            return ['status'=>'error','error'=>$e->getMessage()];
        }
	}
}