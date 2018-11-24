<?php

namespace App\Libraries;

use Illuminate\Http\Request;
use DB;

class LogEvent
{

    public static function addEvent($data)
    {
      $addEvt =  DB::table('log_event')->insert($data);

      if ($addEvt)
      {
        return true;
      }else
      {
        return false;
      }
    }
}

?>