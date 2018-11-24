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
use App\Models\ForgotPasswordModel;
use Illuminate\Support\Facades\Mail;


class ForgotPasswordController extends Controller
{
    /*
        |--------------------------------------------------------------------------
        | Password Reset Controller
        |--------------------------------------------------------------------------
        |
        | This controller is responsible for handling password reset emails and
        | includes a trait which assists in sending these notifications from
        | your application to your users. Feel free to explore this trait.
        |
    */
    use StatusResponse;
    //use SendsPasswordResetEmails;

    /**
     * Create a new controller instance.
     *
     * @return void
    */
    public function __construct()
    {
        $this->middleware('guest');
    }

    public function forgot(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:155'
        ]);

        if ($validator->fails()){
            $data = $validator->getMessageBag()->toArray();
            $message = $validator->errors()->first();
            return $this->_status('VER', $message, $data);
        }

        $d = $request->all();
        
        $check = UserModel::where('email', trim($d['email']))->select(['email', 'first_name'])->first();

       
        if($check == '')
            return $this->_status('ERR', 'Email ID does not exist');

        $token = str_random(60);
        $email = trim($d['email']);
        $p = [

            'email' => $email,
            'token' => $token,
            'status'=> 1,
            'created_at'=>date("Y-m-d H:i:s")
        ];

        $createUser = ForgotPasswordModel::create($p);

        $eventData = [
            'user_id' => $createUser->id,
            'user_ip' => \Request::ip(),
            'event'   => 'Forgot Password',
            'data'    =>  json_encode($p)
        ];

        $addEvt = LogEvent::addEvent($eventData);

        $link = url('https://giltxchange.com/secure/set-password/token/'.$token);
        
        // Sent email to confirmation 
        $this->sendForgotEmail(['first_name'=>$check->first_name, 'email'=>$check->email, 'link'=>$link]);

        return $this->_status('SUCC', 'The link has been sent to your email.');
    }

    private function sendForgotEmail($d)
    {
        $data = [
            'first_name'=>ucfirst($d['first_name']),
            'forgot_link'=>$d['link']
        ];
        $firstName = $d['first_name'];
        $email =  $d['email'];
        Mail::send(['html' => 'emails.forgot'], $data, function ($message) use ($email, $firstName) {
            $message->to($email, $firstName)->subject('Reset Password');
            $message->from('support@giltxchange.com', 'Giltxchange');
        });
    }

    public function set(Request $request)
    {
       $validator = Validator::make($request->all(), [
            'token'         => 'required|string',
            'password'      => 'required|string|min:6',
        ]);

        if ($validator->fails()){
            $data = $validator->getMessageBag()->toArray();
            $message = $validator->errors()->first();
            return $this->_status('VER', $message, $data);
        }

        $d = $request->all(); 

        $user = ForgotPasswordModel::where(['token'=>$d['token'],'status'=>1])->select('email')->first();

        if($user == '')
            return $this->_status('ERR','Invalid User Token');

        $p['password'] = bcrypt($d['password']);

        UserModel::where('email',$user->email)->update([
            'password'=>$p['password']
        ]);

        ForgotPasswordModel::where(['token'=>$d['token']])->update([
            'status'=>0
        ]);
		
		//$request->session()->put('PASSWORD_RESET_SUCC', '1');
        return $this->_status('SUCC','Password has been reset');
    }
}
