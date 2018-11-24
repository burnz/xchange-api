<?php

namespace App\Http\Controllers\Auth;
use App\User;
use App\Models\UserModel;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Traits\StatusResponse;
use App\Libraries\LogEvent;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Auth;
use Ixudra\Curl\Facades\Curl;
use App\Libraries\Common;
use Illuminate\Support\Facades\Mail;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */
    use StatusResponse;
    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->comm = new Common;
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'     => 'required|string|email|max:155',
            'password'  => 'required|string|min:6',
        ]);

        if ($validator->fails()){
            $data    = $validator->getMessageBag()->toArray();
            $message = $validator->errors()->first();
            return $this->_status('VER', $message, $data);
        }else{
            $d     = $request->all();
            $check = UserModel::where('email', $d['email'])->first();
            if($check == '')
                return $this->_status('ERR', 'Email ID does not exist');

            if($check->email_verified == 0) 
                return $this->_status('ENV','Email ID is not verified.');
            
            if($check->status == 0) 
                return $this->_status('ERR','Your account is inactive');

            if($check->auth_enabled == 1)
            {
                if(!isset($d['secret']) || is_null($d['secret']))
                    return $this->_status('VER','Please enter OTP.');
                $p = [
                    'auth_code' => $d['secret'],
                    'user_id'   => $check->id
                ];
                $verify = $this->comm->verify_2fa($p);

                if($verify['status'] == 'error')
                    return $this->_status('ERR',$verify['error']);
            }
            $eventData = [
                'user_id' => $check->id,
                'user_ip' => \Request::ip(),
                'event' => 'User Login',
                'data' => json_encode($d['email'])
            ];
            $addEvt = LogEvent::addEvent($eventData);

            $result = Auth::attempt(['email' => $d['email'], 'password' => $d['password']]);

            if(!$result)
                return $this->_status('ERR', 'Password is Incorrect');
            else{
                $token = str_random(60);
                UserModel::where('email', '=', $d['email'])->update(['token'=>$token]);
                
				
				
				if(isset($check->first_name) && isset($check->email) && $check->email!= ''){
					
					$first_name = $check->first_name;
					$last_name = $check->last_name;
					$email = $check->email;
							
					$this->sendConfirmEmailLogin(['first_name'=>$first_name, 'email'=>$email]);
					
				}else{
					$first_name = "";
					$last_name = "";
					$email = "";
				}
								
				return $this->_status('SUCC', 'User Login Successfully',['token'=>$token,'first_name'=>$first_name,'last_name'=>$last_name,'email'=>$email]);
            }
        }
    }
	
	private function sendConfirmEmailLogin($d)
    {
        return 1;
        
        $data = [
            'first_name'=>ucfirst($d['first_name'])            
        ];
        $firstName = $d['first_name'];
        $email =  $d['email'];
        Mail::send(['html' => 'emails.loginconfirm'], $data, function ($message) use ($email, $firstName) {
            $message->to($email, $firstName)->subject('Sign In to your Xchange account');
            $message->from('support@giltxchange.com', 'Xchange');
        });
    }
}
