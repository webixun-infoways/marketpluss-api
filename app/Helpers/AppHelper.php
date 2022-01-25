<?php

namespace App\Helpers;
class AppHelper
{
      public static function send_sms2($contact,$msg)
      {
            $sms_username ='webixunotp';
			$sendername = 'WEBIXN';
			$smstype   = 'TRANS';
			$apikey   = '2b11088f-109f-4b04-b639-833bc4160fdd';
			$url="http://88.99.147.101/sendSMS?username=$sms_username&message=".urlencode($msg)."&sendername=$sendername&smstype=$smstype&numbers=$contact&apikey=$apikey";
			$ret = file_get_contents($url);
			return $ret;
		

      }
	  
	  public static function send_sms($contact,$msg)
      {
        $apiKey = urlencode('MjJkMjcwNGMwMTQ5NzllM2VhZGQwNmI0MjBiNjMyYjQ=');
		$sender = urlencode('HRABIT');
	
		// Prepare data for POST request
		$data = array('apikey' => $apiKey, 'numbers' => $contact, 'sender' => $sender, 'message' => urlencode($msg));
		
		$url="http://api.textlocal.in/send/?sender=".$sender."&message=".urlencode($msg)."&apikey=".$apiKey."&numbers=".$contact;
			$ret = file_get_contents($url);
			return $ret;

      }
	  
	  public static function send_Push($heading,$url,$user_type,$subscriber,$desc)
      {
		$content      = array(
        "en" => 'English Message'
		);
		
		$heading = array(
		"en" => $heading
		);
		$us=$subscriber;
		$arr=array("field"=>"tag","key"=>"v_id","relation" => "=","value"=>$us);
	
		$fields = array(
        'app_id' => "b8f9d07f-eae9-449c-bceb-c4956a44351f",
		
        'data' => array(
            "foo" => "bar"
        ),
		'included_segments' => array(
            "Subscribed Users"
        ),
        'contents' => $content,
		'headings' => $heading,
		'url'=>$url
		);
    
		$fields = json_encode($fields);
		print("\nJSON sent:\n");
		
		print($fields);
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json; charset=utf-8',
			'Authorization: Basic MjBiMDdhZGMtZjc4Yi00NTVkLTgzZTEtZjIxNDg1NTYyN2Nh'
		));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		
		$response = curl_exec($ch);
		curl_close($ch);
		
		return $response;

      }
		
}
?>