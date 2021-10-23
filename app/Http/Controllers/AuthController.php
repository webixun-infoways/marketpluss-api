<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;

class AuthController extends Controller
{
	//method for contact verification 
    public function mobile_verification(request $request)
	{
		$validator = Validator::make($request->all(), [ 
            'contact' => 'required', 
			'verification_type' => 'required'
        ]);
		
		if ($validator->fails())
    	{
        	return response(['errors'=>$validator->errors()->all()], 422);
    	}
		
		$contact=$request->contact;
		
//		$otp=rand(100000,999999);
		$otp=Hash::make("1234");
		
		if($request->verification_type=='user')
		{
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
		}
		else if($request->verification_type=='vendor'){

			$data = Vendor::where('contact', $contact)->get();

			if ($data->count()==0)
			{
				$user = new Vendor;
				$user->contact = $request->contact;
 				$user->password =$otp;
        		$user->save();
			}
			else
				$user = Vendor::where('contact',$contact)->update(array('password' =>$otp));
		}
		else{
			return response()->json(['error' => 'Unauthorized Access!'], 401);
		}
		
		$msg = "Use $otp as your OTP for shvetdhardhara account verification. This is confidential. Please, do not share this with anyone. Webixun infoways PVT LTD ";
		
		// $obj=new  ComponentConfig();
		// $image_data= $obj->send_sms($contact,$msg);

		$response['msg']='ok';
		echo json_encode($response);
	}


	//method for otp verification 
	public function otp_verification(request $request)
	{
		$validator = Validator::make($request->all(), [ 
            'contact' => 'required', 
            'otp' => 'required', 
        ]);
		
		if ($validator->fails())
    	{
        	return response(['errors'=>$validator->errors()->all()], 422);
    	}

		if($request->verification_type=='user')
		{
			$user = User::where("contact", $request->contact)->first();
        	
			if(!isset($user)){
				return response()->json(['error' => 'Account not found.'], 401);
       		}
        	
			if (!Hash::check($request->otp, $user->password)) 
			{
				return response()->json(['error' => 'Invalid OTP, Try Again.'], 401);
        	}
	
			$tokenResult = $user->createToken('User');
        	$user->access_token = 'Bearer '.$tokenResult->accessToken;
        	// $user->token_type = ;

			if($user->name == "")
			{
            	//now return this token on success login attempt
				$response = ['msg' => 'ok','token' => $user->access_token,'user_type' => 'register','usr' => Crypt::encryptString($user->id)];
			}
			else
			{
				 //now return this token on success login attempt
				$response = ['msg' => 'ok','token' => $user->access_token,'user_type' => 'login','usr' => Crypt::encryptString($user->id)];
			
			}

			return $response;
		}
		else if ($request->verification_type=='vendor'){
			
			$vendor = Vendor::where("contact", $request->contact)->first();
        	
			if(!isset($vendor)){
				return response()->json(['error' => 'Account not found.'], 401);
       		}
        	
			if (!Hash::check($request->otp, $vendor->password)) 
			{
				return response()->json(['error' => 'Invalid OTP, Try Again.'], 401);
        	}
        	
			$tokenResult = $vendor->createToken('Vendor');
        	$vendor->access_token = 'Bearer '.$tokenResult->accessToken;
        	// $user->token_type = ;

			if($vendor->name == "")
			{
            	//now return this token on success login attempt
				$response = ['msg' => 'ok','token' => $vendor->access_token,'user_type' => 'register','usr' => Crypt::encryptString($vendor->id)];
			}
			else
			{
				 //now return this token on success login attempt
				$response = ['msg' => 'ok','token' => $vendor->access_token,'user_type' => 'login','usr' => Crypt::encryptString($vendor->id)];
			
			}
			return $response;
		}
		else{
			return response()->json(['error' => 'Unauthorized Access!'], 401);
		}
	}

	public function update_profile_name(request $request)
	{
		echo "callling";
	}

	public function unauthorized()
	{
		return response()->json(['error' => 'Unauthorized Access!'], 401);
	}

}
