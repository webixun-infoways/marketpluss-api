<?php

namespace App\Helpers;
class AppHelper
{
      public static function send_sms($contact,$msg)
      {
            $sms_username ='webixunotp';
			$sendername = 'WEBIXN';
			$smstype   = 'TRANS';
			$apikey   = '2b11088f-109f-4b04-b639-833bc4160fdd';
			$url="http://88.99.147.101/sendSMS?username=$sms_username&message=".urlencode($msg)."&sendername=$sendername&smstype=$smstype&numbers=$contact&apikey=$apikey";
			$ret = file_get_contents($url);
			return $ret;
		

      }
	  
	  public static function send_sms2($contact,$msg)
      {
        $apiKey = urlencode('MjJkMjcwNGMwMTQ5NzllM2VhZGQwNmI0MjBiNjMyYjQ=');
		$sender = urlencode('HRABIT');
	
		// Prepare data for POST request
		$data = array('apikey' => $apiKey, 'numbers' => $contact, 'sender' => $sender, 'message' => urlencode($msg));
		
		$url="https://api.textlocal.in/send/?sender=".$sender."&message=".urlencode($msg)."&apikey=".$apiKey."&numbers=".$contact;
			$ret = file_get_contents($url);
			return $ret;

      }
		
}
?>