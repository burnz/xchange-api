<?php

namespace App\Http\Controllers\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Traits\StatusResponse;
use App\Libraries\LogEvent;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Support\Facades\Auth;
use App\Models\UserModel;
use App\Libraries\Common;

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */
    use StatusResponse;
    use ResetsPasswords;

    /**
     * Where to redirect users after resetting their password.
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
        $this->middleware('guest');

        $this->comm = new Common;
    }

    public function reset(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token'         => 'required|string',
            'old_password'  => 'required|string|min:6',
            'new_password'  => 'required|string|min:6',
        ]);

        if ($validator->fails()){
            $data = $validator->getMessageBag()->toArray();
            $message = $validator->errors()->first();
            return $this->_status('VER', $message, $data);
        }

        $d = $request->all();

        $user = UserModel::where('token',$d['token'])->select('id','customer_id','auth_enabled')->first();

        if($user == '')
            return $this->_status('ERR','Invalid User Token');

        if($user->auth_enabled == 1)
        {
            if(!isset($d['secret']) || is_null($d['secret']))
                return $this->_status('VER','Please enter 2FA Code.');
            $v = [

                'auth_code' => $d['secret'],
                'user_id'   => $user->id 
            ];

            $verify = $this->comm->verify_2fa($v);

            if($verify['status'] == 'error')
                return $this->_status('ERR', $verify['error']);
        }

        $result = Auth::attempt(['token' => $d['token'], 'password' => $d['old_password']]);

        if(!$result)
            return $this->_status('ERR', 'Password is Incorrect');
        else{

            $p['password'] = bcrypt($d['new_password']);

            UserModel::where('id',$user->id)->update([
                'password'=>$p['password']
            ]);

            return $this->_status('SUCC','Password has been reset');
        }
    }
}
