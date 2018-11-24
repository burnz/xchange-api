<?php


namespace App\Traits;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;


trait StatusResponse
{

    public function __construct(){
        
        $this->default_currency = 'INR';
        $this->max_deposit_limit=25000;
        $this->min_deposit_limit=1;
       
    }

    public function _status($key = "", $msg = "", $data = []) {
        $d = "ERR";
        switch ($key):
            case 'TXN':$d = 'Transaction Successful';
                break;

            case 'TUP':$d = 'Transaction Under Process';
                break;

            case 'IRA':$d = 'Invalid Refill Amount';
                break;

            case 'RNF':$d = 'Remitter not found';
                break;

            case 'RAR':$d = 'Remitter Already Registered';
                break;

            case 'IVC':$d = 'Invalid Verification Code or OTP';
                break;

            case 'IAN':$d = 'Invalid Account Number';
                break;

            case 'IAB':$d = 'Insufficient Wallet Balance';
                break;

            case 'RBT':$d = 'Refill Barred Temporarily';
                break;

            case 'ISE':$d = 'System Error, Try Later';
                break;

            case 'IAT':$d = 'Invalid Access Token';
                break;

            case 'SPD':$d = 'Service Provider Downtime';
                break;

            case 'SPE':$d = 'Service Provider Error, Try Later';
                break;

            case 'ITI':$d = 'Invalid Transaction ID';
                break;

            case 'DTX':$d = 'Duplicate Transaction, Try Later';
                break;

            case 'RPI':$d = 'Request Parameters are Invalid or Incomplete';
                break;

            case 'AAB':$d = 'Account Blocked, Contact Helpdesk';
                break;

            case 'UAD':$d = 'User Access Denied';
                break;

            case 'TRP':$d = 'Transaction Refund Processed, Wallet Credited';
                break;

            case 'TDE':$d = 'Transaction Dispute Error, Contact Helpdesk';
                break;

            case 'DLS':$d = 'Dispute Logged Successfully';
                break;

            case 'DID':$d = 'Duplicate Agent Transaction ID';
                break;

            case 'OUI':$d = 'Outlet Unauthorized or Inactive';
                break;

            case 'ODI':$d = 'Outlet Data Incorrect';
                break;

            case 'IUA':$d = 'Outlet Data Incorrect';
                break;

            case 'SNA':$d = 'Service not available';
                break;

            case 'IPM':$d = 'Invalid Parameter';
                break;

            case 'INN':$d = 'Invalid name';
                break;

            case 'IRA':$d = 'Invalid Remitter Address';
                break;

            case 'IPC':$d = 'Invalid Pincode';
                break;

            case 'ICN':$d = 'Invalid cityname';
                break;

            case 'ISN':$d = 'Invalid statename';
                break;

            case 'IDF':$d = 'Invalid idproof';
                break;

            case 'IFN':$d = 'Invalid idproof number';
                break;

            case 'RAA':$d = 'Invalid Remitter address';
                break;

            case 'IPC':$d = 'Invalid pincode';
                break;

            case 'ITN':$d = 'Invalid Title';
                break;

            case 'IGN':$d = 'Invalid Gender';
                break;

            case 'IGR':$d = 'Invalid Religion';
                break;

            case 'ICA':$d = 'Invalid Category';
                break;

            case 'IED':$d = 'Invalid Education';
                break;

            case 'ILN':$d = 'Invalid Last name';
                break;

            case 'INA':$d = 'Invalid nominee age';
                break;

            case 'IRS':$d = 'Invalid Relationship';
                break;

            case 'IDB':$d = 'Invalid Date of birth';
                break;

            case 'IMS':$d = 'Invalid Marital Status';
                break;

            case 'INS':$d = 'Invalid Nationality';
                break;

            case 'IRR':$d = 'Invalid Residential Status';
                break;

            case 'IEI':$d = 'Invalid Email id';
                break;

            case 'IIN':$d = 'Invalid Id Proof Name';
                break;

            case 'IAP':$d = 'Invalid Address Proof Name';
                break;

            case 'ENV':$d = 'User Email Not Verified';
                break;

            case 'ATC':$d = 'Accept AML Terms';
                break;

            case 'IUP':$d = 'Incomplete User profile';
                break;

            case 'VER':$d = 'Validation Error';
                break;

            case 'SUCC':$d = 'Request Successfully Accepted';
                break;

            case 'FAIL':$d = 'Request Failed';
                break;
            case 'BITGOERR':$d = 'BITGO API ERROR';
                break;
            case 'ERR':$d = 'Unknown Error';
                break;

            default:$d = '';
        endswitch;

        if ($d == '') {
            $d = $key;
            $key = "ERR";
        }

        $res["statuscode"] = $key;
        $res["message"]     = $d;

        if ($msg != "") {
            $res["message"] = $msg;
        }

        $res["data"] = $data;


        /*if($key=='BITGOERR')
        {

            $data = [
                "sms" => $msg,
                "d" => json_encode($data)
            ];

            $email = 'deepoo@lalaworld.io';
            $firstName = 'LALA World';

            Mail::send(['html' => 'emails.error-mail'], $data, function ($message) use ($email, $firstName) {

                $message->to($email, $firstName)
                    ->subject('BITGO Error');
                $message->from('hello@lalaworld.io', 'Lala World');
            });

        }*/

        return $res;
    }
}
