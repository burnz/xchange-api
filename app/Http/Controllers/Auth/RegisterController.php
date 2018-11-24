<?php

namespace App\Http\Controllers\Auth;

use App\User;
use App\Models\UserModel;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;
use App\Traits\StatusResponse;
use PragmaRX\Google2FA\Google2FA;
use App\Models\ReferralModel;
use App\Libraries\LogEvent;
use Illuminate\Support\Facades\Mail;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */
    use StatusResponse;
    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    //protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
        
        $this->google2fa  = new Google2FA();

        $this->google2fa->setAllowInsecureCallToGoogleApis(true);
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'first_name'    => 'required|alpha|max:45|min:2',
            'last_name'     => 'required|alpha|max:45|min:2',
            'email'         => 'required|string|email|max:155|unique:users,email',
            'password'      => 'required|string|min:6|confirmed',
            'mobile'        => 'required|numeric|min:10|unique:users,mobile',
            'terms'         => 'required|in:0,1'
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\User
     */
    /*protected function create(array $data)
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
        ]);
    }*/

    public function register(Request $request)
    {
        
        $validator = $this->validator($request->all());

        if ($validator->fails())
        {
            $data       = $validator->getMessageBag()->toArray();
            $message    = $validator->errors()->first();
            return $this->_status('VER', $message, $data);
        }else{
            $d          = $request->all();
            $r_code     = str_random(15);
            $cust_id    = str_random(8);
            $secretKey  = $this->google2fa->generateSecretKey();
            $google2fUrl= $this->google2fa->getQRCodeGoogleUrl('GiltExchange',$d['email'],$secretKey);
            $token      = str_random(60);
            $record     = [
                'first_name'       => $d['first_name'],
                'last_name'        => $d['last_name'],
                'email'            => $d['email'],
                'password'         => bcrypt($d['password']),
                'terms_conditions' => $d['terms'],
                'token'            => $token,
                'google_auth_code' => $secretKey,
                'referal_code'     => $r_code,
                'customer_id'      => $cust_id,
                'dial_code'        => '+91',
                'mobile'           => $d['mobile'],
                'country'          => 'India',
                'auth_code_url'    => $google2fUrl
            ];

            if(isset($d['coupon']) && !empty($d['coupon']))
            {
                $refer_by_exist = User::where('referal_code','=',$d['coupon'])->count('id');

                if(!$refer_by_exist)
                    return $this->_status('ERR', 'Invalid Coupon Code');

                $createUser = UserModel::create($record);

                if(!$createUser)
                    return $this->_status('ERR', 'User Registration Failed');

                unset($record['password']);

                $eventData = [
                    'user_id' => $createUser->id,
                    'user_ip' => \Request::ip(),
                    'event'   => 'Users Registration',
                    'data'    =>  json_encode($record)
                ];

                $addEvt = LogEvent::addEvent($eventData);

                $refData = [
                    'user_id'       => $createUser->id,
                    'referred_code' => $d['coupon'],
                    'status'        => 0
                ];

                $ref = ReferralModel::create($data);

                return $this->_status('SUCC', 'User Successfully Registered');
            }else{

                $createUser = UserModel::create($record);

                if(!$createUser)
                    return $this->_status('ERR', 'User Registration Failed');

                unset($record['password']);

                $eventData = [
                    'user_id' => $createUser->id,
                    'user_ip' => \Request::ip(),
                    'event'   => 'Users Registration',
                    'data'    =>  json_encode($record)
                ];

                $addEvt = LogEvent::addEvent($eventData);

                
                $link = 'https://giltxchange.com/secure/confirm-email?hash='.$r_code;
                // Sent email to confirmation 
                $this->sendConfirmEmail(['first_name'=>$d['first_name'], 'email'=>$d['email'], 'link'=>$link]);
                
                return $this->_status('SUCC', 'User Successfully Registered');
            }
        }
    }

    private function sendConfirmEmail($d)
    {
        $data = [
            'first_name'=>ucfirst($d['first_name']),
            'confim_email'=>$d['link']
        ];
        $firstName = $d['first_name'];
        $email =  $d['email'];
        Mail::send(['html' => 'emails.confirm'], $data, function ($message) use ($email, $firstName) {
            $message->to($email, $firstName)->subject('Email confirmation link');
            $message->from('support@giltxchange.com', 'Giltxchange');
        });
    }
}
