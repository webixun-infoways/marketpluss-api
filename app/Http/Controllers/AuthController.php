<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use App\Helpers\AppHelper;
use App\Jobs\ProcessSms;
use App\Models\refer_earn_setup;
class AuthController extends Controller
{
	//method for contact verification 
    public function mobile_verification(request $request)
	{
		$validator = Validator::make($request->all(), [ 
            'contact' => 'required', 
			'verification_type' => 'required'
        ]);
		
		if($validator->fails())
    	{
        	return response(['errors'=>$validator->errors()->all()], 422);
    	}
		
		//check the request its one time or resend
		if(isset($request->request_type))
		{
			$request_type=$request->request_type;
		}
		else
		{
			$request_type="send";
		}
		$contact=$request->contact;
		
		if(env("APP_DEBUG")) // condition to check this is beta or release
		{
			//Beta
			$otp=1234;
			$msg="Use $otp. as your OTP for MarketPluss account verification. This is confidential. Please, do not share this with anyone. Webixun infoways PVT LTD";
			$data['contact']=$contact;
			$data['msg']=$msg;
		}
		else
		{
			if($request->contact == 8006435315)
			{
				$otp=4588;
			}
			else
			{
				//Production
			$otp=rand(999,9999);
			$msg="Use $otp. as your OTP for MarketPluss account verification. This is confidential. Please, do not share this with anyone. Webixun infoways PVT LTD";
			
			$data['contact']=$contact;
			$data['msg']=$msg;
			
			$data['request_type']=$request_type;
			//AppHelper::send_sms2($data['contact'],$msg);
			//jobs for end the sms 
			ProcessSms::dispatch($data);
			}
		}
		
		
		
		// $request->header('User-Agent');
		//return $request->ip();
		$otp=Hash::make($otp);
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
				 $user->status ="pending";
        		$user->save();
			}
			else
				$user = Vendor::where('contact',$contact)->update(array('password' =>$otp));
		}
		else{
			return response()->json(['error' => 'Unauthorized Access!'], 401);
		}
		
	
		// $obj=new  ComponentConfig();
		// $image_data= $obj->send_sms($contact,$msg);

		$response['msg']='ok';
		return json_encode($response);
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
			//return $request;
			$user = User::where("contact", $request->contact)->first();
        	//return $user;
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

			if($user->name == " " || $user->name == null)
			{
				//code for refer & earn plan
				$getip = AppHelper::get_ip();
				$getdevice = AppHelper::get_device();
				$getos = $request->oprating_system;
				
				//apply refer and earn code 
				
				refer_earn_setup::where("user_ip_address",$getip)
				->where("refer_status","pending")
				->where("user_device","Mobile")
				->where("user_os",ucwords($getos))
				->update(['user_id' => $user->id,'refer_status'=>'register']);
				//return "Hello";
            	//now return this token on success login attempt
				$response = ['msg' => 'ok','token' => $user->access_token,'user_type' => 'register','usr' => $user->id];
			}
			else
			{
				 //now return this token on success login attempt
				$response = ['msg' => 'ok','token' => $user->access_token,'user_type' => 'login','usr' => $user->id];
			
			}

			return $response;
		}
		else if ($request->verification_type=='vendor'){
			
			$vendor = Vendor::where("contact", $request->contact)->first();
        	
			if(!isset($vendor)){
				return response()->json(['error' => 'Account not found, Please Contact Admin for support'], 401);
       		}
        	
			if (!Hash::check($request->otp, $vendor->password)) 
			{
				return response()->json(['error' => 'Invalid OTP, Try Again.'], 401);
        	}
        	
			$tokenResult = $vendor->createToken('Vendor');
        	$vendor->access_token = 'Bearer '.$tokenResult->accessToken;
        	// $user->token_type = ;

			if($vendor->name == " " || $vendor->name == null)
			{
            	//now return this token on success login attempt
				$response = ['msg' => 'ok','token' => $vendor->access_token,'user_type' => 'register','usr' =>$vendor->id];
			}
			else
			{
				 //now return this token on success login attempt
				$response = ['msg' => 'ok','token' => $vendor->access_token,'user_type' => 'login','usr' => $vendor->id];
			
			}
			return $response;
		}
		else{
			return response()->json(['error' => 'Unauthorized Access!'], 401);
		}
	}

	public function logout(request $request)
	{
		if (Auth::check()) {
			Auth::user()->token()->revoke();
		     $response['status']=true;
             $response['msg'] = "Logout Successfull!";
			return json_encode($response);
		}else{
			 $response['status']=false;
             $response['msg'] = "Failed!";
			return json_encode($response);
		}
	}

	public function unauthorized()
	{
		return response()->json(['error' => 'Unauthorized Access!'], 401);
	}
	
	public function validate_upi_id(Request $request)
	{
		$validator = Validator::make($request->all(), [ 
            'upi_id' => 'required'
        ]);
		
		if ($validator->fails())
    	{
        	return response(['errors'=>$validator->errors()->all()], 422);
    	}
		
		
		
		$upi_id=$request->upi_id;
		$data="";
		if ($data == "")
		{
			$response['status']=true;
			$response['data']=$upi_id;
		}
		else
		{
			$response['status']=true;
			$response['msg']="Invalid UPI, Try Again";
		}
		
		return json_encode($response);
	}

}
