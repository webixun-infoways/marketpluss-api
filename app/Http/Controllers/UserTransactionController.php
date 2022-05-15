<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\permission_page;
use App\Models\point_level;
use App\Models\user_txn_log;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Notification;
use App\Models\permission_user_page;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;
use Session;
use App\Jobs\ProcessPush;

use paytm\paytmchecksum\paytmchecksum;

class UserTransactionController extends Controller
{
    public function credit_coin($user_id,$comment,$txn_amount,$txn_status,$txn_type){
	//    $amount = user_txn_log::where('user_id',$user_id)->whereDate('created_at', DB::raw('CURDATE()'))->sum('txn_amount');
	//    $max_amount_per_day = point_level::get('max_point_per_day');
	//    if($max_amount_per_day[0]->max_point_per_day > $amount){
		   
		   $txn_id=$user_id.time().uniqid(mt_rand(),true);
		   $res = new user_txn_log;
		   $res->user_id = $user_id;
		   $res->txn_id = $txn_id;
		   $res->txn_amount = $txn_amount;
		   $res->txn_status = $txn_status;
		   $res->txn_type = $txn_type;
		   //$res->comment=$comment;
		   $res->comment=$comment;
		   
		   if($res->save()){
			   //update user wallet
			   $user=User::find($user_id);
			   $user->wallet=$user->wallet+$txn_amount;
			   $user->save();
			   
			   return response()->json(['status'=>true,'msg'=>'Cashback Intitiated!']);
		   }else{
			   return response()->json(['status'=>false,'error'=>'Something Went Wrong!']);
		   }
	//    }else{
	// 	   return response()->json(['status'=>false,'error'=>'Maximum amount for per day earning exceed!']);
	//    }
	   
	}
	

	public function verifyTransaction(Request $request)
	{
		$validator = Validator::make($request->all(), [ 
             'upi_id'=> 'required',
			 'transfer_amount'=> 'required'
         ]);
		 
		 
		 if ($validator->fails())
         {
             return response(['error'=>$validator->errors()->all()], 422);
         }
		 
		 $user_id=Auth::user()->id;
		 $txn_id=$user_id.time().uniqid(mt_rand(),true);
		 
		 if(Auth::user()->wallet>=$request->transfer_amount)
		 {
			 $mid=env("PAYTM_MID");
			 $key=env("PAYTM_MERCHANT_KEY");
			 $paytmParams = array();
			
			 $paytmParams["body"] = array(
				"mid"                  => $mid,
				"referenceId"          => "ref_987654321",
				"cardPreAuthType"      => "STANDARD_AUTH",
				"preAuthBlockSeconds"  => "12321"
				);

				/*
				* Generate checksum by parameters we have in body
				* Find your Merchant Key in your Paytm Dashboard at https://dashboard.paytm.com/next/apikeys 
				*/
				$checksum = PaytmChecksum::generateSignature(json_encode($paytmParams["body"], JSON_UNESCAPED_SLASHES), $key);

				$paytmParams["head"] = array(
					"tokenType"     => "CHECKSUM",
					"token"	    => $checksum
				);

				$post_data = json_encode($paytmParams, JSON_UNESCAPED_SLASHES);

				if(env("APP_DEBUG")) // condition to check this is beta or release
				{
					$url = "https://securegw-stage.paytm.in/theia/api/v1/token/create?mid=$mid&referenceId=ref_987654321";

				}
				else
				{
					$url = "https://securegw.paytm.in/theia/api/v1/token/create?mid=$mid&orderId=ORDERID_98765";

				}
				/* for Staging */
				
				/* for Production */
				
				$ch = curl_init($url);
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
				curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json")); 
				$response = curl_exec($ch);

				$response=json_decode($response);
				// print_r($response->body->accessToken);

				$access_token=$response->body->accessToken;


				$paytmParams = array();

				$paytmParams["body"] = array(
					"vpa"      => $request->upi_id,
					"mid"    => $mid,
				);

				$paytmParams["head"] = array(
					"tokenType"     => "ACCESS",
					'token'         => $access_token
				);

				$post_data = json_encode($paytmParams, JSON_UNESCAPED_SLASHES);

				if(env("APP_DEBUG")) // condition to check this is beta or release
				{
					$url = "https://securegw-stage.paytm.in/theia/api/v1/vpa/validate?mid=$mid&referenceId=ref_987654321";
				}
				else
				{
					$url = "https://securegw.paytm.in/theia/api/v1/vpa/validate?mid=$mid&referenceId=ref_987654321";
			
				}

				/* for Staging */
				//
				/* for Production */
				$ch = curl_init($url);
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array(
					"Content-Type: application/json"
				));
				$response = curl_exec($ch);

				$response=json_decode($response);

				if($response->body->valid)
				{
					$point=point_level::all();

					$bank_transfer_limit=$point[0]->bank_transfer_limit;
					$txn_charges=$point[0]->txn_charges;

					if($request->transfer_amount>=$bank_transfer_limit)
					{
						$transfer_amount=$request->transfer_amount;
						$processing_fee=$txn_charges;
						$process_amount=($request->transfer_amount*$processing_fee/100);
						$final_amount=$transfer_amount-$process_amount;

						//update upi_id to profile
						$user=User::find($user_id);
			   			$user->upi_id=$request->upi_id;
			   			$user->save();

						$response=array();
						$response['status']=true;
						$response['data']['transfer_amount']=$transfer_amount;
						$response['data']['processing_fee']=$processing_fee."%";
						$response['data']['processing_amount']=$process_amount;
						$response['data']['final_amount']=$final_amount;
						$response['data']['upi']=$request->upi_id;
						$response['data']['balance']=Auth::user()->wallet-$request->transfer_amount;
						return json_encode($response);
					}
					else
					{
						return response()->json(['status'=>false,'error'=>'Your Amount should be grator then '.$bank_transfer_limit]);
					}
				}
				else
				{
					return response()->json(['status'=>false,'error'=>'Invalid UpiID, Please try agian']);
				}
			   
			   
		   }
		   
		 
		 else
		 {
			    return response()->json(['status'=>false,'error'=>'Balance amount is low!']);
		 }
	   
	   }


	
	public function transfer_to_bank(Request $request)
	{
		$validator = Validator::make($request->all(), [ 
			 'transfer_amount'=> 'required'
         ]);
		 
		 
		 if ($validator->fails())
         {
             return response(['error'=>$validator->errors()->all()], 422);
         }

		 return response()->json(['status'=>false,'error'=>'Something Went Wrong!']);
		 
		 $user_id=Auth::user()->id;
		 $txn_id=$user_id.time().uniqid(mt_rand(),true);
		 
		if(Auth::user()->wallet>=$request->transfer_amount)
		{
			 
		  	$res = new user_txn_log;
		   	$res->user_id = $user_id;
		   	$res->txn_id = $txn_id;
		   	$res->txn_amount = $request->transfer_amount;
		   	$res->txn_status = 'pending';
		   	$res->txn_type = 'debit';
			
			   $msg = "Money has been successfully transfered to your bank account";
		   	$res->comment=$msg;
		   	//$res->save();
		   	if($res->save()){
			   //update user wallet
			   $user=User::find($user_id);
			   $user->wallet=$user->wallet-$request->transfer_amount;
			   $user->save();
			   return response()->json(['status'=>true,'msg'=>'Cashback Intitiated, It will take 36-48 Hours to reflect.']);
		   	}else{

			   return response()->json(['status'=>false,'error'=>'Something Went Wrong!']);
		   	}
		 }
		 else
		 {
			    return response()->json(['status'=>false,'error'=>'Balance amount is low!']);
		 }
	   
	   }


	   //get_payments for vendors

	    //get vendor data 
		public function get_vendors_for_payment(Request $request)
		{
			$validator = Validator::make($request->all(), [ 
				'latitude'=>'required',
				'longitude'=>'required',
			]);
	
			if ($validator->fails())
			{
				return response(['errors'=>$validator->errors()->all()], 422);
			}
	
	
			$haversine = "(6371 * acos(cos(radians(" . $request->latitude . ")) 
			* cos(radians(`shop_latitude`)) 
			* cos(radians(`shop_longitude`) 
			- radians(" . $request->longitude . ")) 
			+ sin(radians(" . $request->latitude . ")) 
			* sin(radians(`shop_latitude`))))";
			
			
				$data=Vendor::with('offer')->with('today_timing')->with('favourite_my')
				->select("vendors.is_prime","vendors.status","vendors.id",'vendors.shop_name','vendors.profile_pic','vendors.address','vendors.current_rating','vendors.flat_deal_all_time')
				->where('vendors.status','Active')->where('vendors.payment_accept',1)->selectRaw("{$haversine} AS distance")
				->having('distance','<','25')
				->orderBy('distance')->orderBy('flat_deal_all_time','DESC')
				->paginate(10);
		
			//return $data;
			
			if(count($data)>0)
			{
				$response['status']=true;
				$response['data']=$data;
			}
			else{
				
				$response['status']=false;
				$response['msg']="No Data Found.";
			}
	
			echo json_encode($response,JSON_UNESCAPED_SLASHES); 
		}
		
	   
	  // else{
		//   return response()->json(['status'=>false,'error'=>'Maximum amount for per day earning exceed!']);
	   //}
	}

