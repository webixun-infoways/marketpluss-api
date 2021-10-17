<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;

class AuthController extends Controller
{
    public function mobile_verification(request $request)
	{
		$validator = Validator::make($request->all(), [ 
            'contact' => 'required', 
        ]);
		
		
		if ($validator->fails())
    	{
        	return response(['errors'=>$validator->errors()->all()], 422);
    	}
		
		
		
		$contact=$request->contact;
		
//		$otp=rand(100000,999999);
		$otp=Hash::make("1234");
		
		
		$data = User::where('contact', $contact)->get();
		
		if ($data->count()==0)
		{
			$user = new User;
			$user->contact = $request->contact;
 			$user->password =$otp;
        	$user->save();
			
		}
		else
			$user = User::where('contact',$contact)->update(array('password' =>$otp));
			
		$msg = "Use $otp as your OTP for shvetdhardhara account verification. This is confidential. Please, do not share this with anyone. Webixun infoways PVT LTD ";
		$obj=new  ComponentConfig();
		$image_data= $obj->send_sms($contact,$msg);
		
		$response['msg']='ok';
		echo json_encode($response);
	}
}
