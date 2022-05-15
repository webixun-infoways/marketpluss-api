<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Vendor;
use App\Models\vendor_main_categories;
use App\Models\Vendor_category;
use App\Models\Vendor_Offer;
use App\Models\User;
use App\Models\Vendor_Offer_Product;
use App\Models\Vendor_Shop_Visit;
use App\Models\Vendors_Subsciber;
use App\Models\vendor_rating;
use App\Models\Vendor_cover;
use App\Models\Vendor_Product;
use App\Models\UserOrders;
use App\Models\Category;
use App\Models\Notification;
use App\Jobs\ProcessPush;
use App\Models\Feed_Save;
use App\Models\Feed;
use App\Models\vendor_timing;
use App\Models\user_orders_txn_log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use paytm\paytmchecksum\paytmchecksum;
use Storage;

class UserOrderController extends Controller
{
    public function fetch_orders_user(Request $request){

        $user_id=Auth::user()->id;
        $data=UserOrders::with('vendor')->where('user_id',$user_id)->orderByDesc('id')->paginate(20);;
        if(count($data)>0)
        {
            $response['status']=true;
            $response['data']=$data;
        }
        else
        {
            $response['status']=false;
            $response['data']="Order ID is not valid";
        }
        return   json_encode($response,JSON_UNESCAPED_SLASHES);

	}

    public function fetch_cashback_order_details_user(Request $request){
        $validator = Validator::make($request->all(), [ 
            'order_code' => 'required', 
        ]);
		//return Auth::user()->id;

		if ($validator->fails())
    	{
        	return response(['errors'=>$validator->errors()->all()], 422);
    	}

        $data=UserOrders::with('vendor')->where('user_id',Auth::user()->id)->where('order_code',$request->order_code)->orderByDesc('id')->get();
        if(count($data)>0)
        {
            $response['status']=true;
            $response['data']=$data;
        }
        else
        {
            $response['status']=false;
            $response['data']="Order ID is not valid";
        }
        return   json_encode($response,JSON_UNESCAPED_SLASHES);
    }


    public function calculate_order_discount(Request $request){
		//return $request;
		 $validator = Validator::make($request->all(), [ 
            'vendor_id' => 'required', 
			'amount' => 'required', 
        ]);
		//return Auth::user()->id;

		if ($validator->fails())
    	{
        	return response(['errors'=>$validator->errors()->all()], 422);
    	}

        $vendor_data=Vendor::where('id',$request->vendor_id)->get(['flat_deal_first_time','flat_deal_all_time','payment_accept']);
        $all_discount= $vendor_data[0]['flat_deal_all_time'];

        $wallet=Auth::user()->wallet;
        //create new order
        $discount=floor($request->amount*$all_discount/100);
        $order_amount=$request->amount;
        $final_amount=$request->amount-$discount;
        
        if($discount>0)
        {
            $response['status']=true;
            $response['discount']=$discount;
            $response['final_amount']=$final_amount;

            if($final_amount>=$wallet)
            {
                $response['wallet']=$wallet;
            }
           else
           {
            $response['wallet']=$final_amount;
           }
            $response['payment_accept']=$vendor_data[0]['payment_accept'];
            // $response['charges'][0]['name']="Service Fee";
            // $response['charges'][0]['amount']=25;
            // $response['charges'][0]['type']="add";
        }
        else
        {
            $response['status']=false;
            $response['msg']="No Deals Available";
        } 
        

        return json_encode($response);
	}


    public function request_cashback_order(Request $request){
		//return $request;
		 $validator = Validator::make($request->all(), [ 
            'vendor_id' => 'required', 
			'amount' => 'required', 
        ]);
		//return Auth::user()->id;

		if ($validator->fails())
    	{
        	return response(['errors'=>$validator->errors()->all()], 422);
    	}

        $vendor_data=Vendor::where('id',$request->vendor_id)->get(['flat_deal_first_time','flat_deal_all_time']);
        $all_discount= $vendor_data[0]['flat_deal_all_time'];
        $order_amount=$request->amount;
        //create new order
        $discount=floor($order_amount*$all_discount/100);
        
        $final_amount=$order_amount-$discount;
        
        $order_code="MP-".Auth::user()->id.floor(time()-999999999);

        $order=new UserOrders();
        $order->order_code= $order_code;
        $order->order_amount =$order_amount;
        $order->total_amount=$final_amount;
        $order->order_discount=$discount;
        $order->order_status='pending';
        $order->order_for="cashback";
        $order->vendor_id=$request->vendor_id;
        $order->user_id=Auth::user()->id;

        if($order->save())
        {
            $response['status']=true;
            $response['discount']=$discount;
            $response['final_amount']=$final_amount;
            $response['code']=$order_code;
            $response['msg']="Request Created!";

            //send notification to vendor
            $heading_user= Auth::user()->name." Requested a new order with you.";
            $post_url=env('NOTIFICATION_VENDOR_URL')."/ViewOrder/".$order_code;
            ProcessPush::dispatch($heading_user,$post_url,$request->vendor_id,'vendor','');
			

        }
        else
        {
            $response['status']=false;
            $response['msg']="request could not be Created!";
        }

        return json_encode($response);
	}


    public function payonlineorder(Request $request)
    {
        //return $request;
		 $validator = Validator::make($request->all(), [ 
            'vendor_id' => 'required', 
			'amount' => 'required', 
            'wallet_check'=>'required'
        ]);
		//return Auth::user()->id;

		if ($validator->fails())
    	{
        	return response(['errors'=>$validator->errors()->all()], 422);
    	}

        $vendor_data=Vendor::where('id',$request->vendor_id)->get(['flat_deal_first_time','flat_deal_all_time']);
        $all_discount= $vendor_data[0]['flat_deal_all_time'];
        $order_amount=$request->amount;
        //create new order
        $discount=floor($order_amount*$all_discount/100);
        
        $final_amount=$order_amount-$discount;
        
        $order_code="MP-".Auth::user()->id.floor(time()-999999999);

        $order=new UserOrders();
        $order->order_code= $order_code;
        $order->order_amount =$order_amount;
        $order->total_amount=$final_amount;
        $order->order_discount=$discount;
        $order->order_status='failed';
        $order->order_for="payonline";
        $order->vendor_id=$request->vendor_id;
        $order->user_id=Auth::user()->id;

        if($order->save())
        {
            $order_id=$order->id;
            if($request->wallet_check)
            {
                $wallet=Auth::user()->wallet;

                if($wallet>=$final_amount)
                {
                    $txn_id=$user_id.time().uniqid(mt_rand(),true);

                    $txn=new user_order_txn_log;
                    $txn->order_id=$order_id;
                    $txn->txn_amount=$final_amount;
                    $txn->txn_method='wallet';
                    $txn->txn_status='success';
                    $txn->payment_txn_id=$txn_id;
                    
                    if($txn->save())
                    {

                        $order=UserOrders::find($order_id);

                        $order->order_status='completed';
                        if($order->save())
                        {
                        //update txn in user wallet log
                                $res = new user_txn_log;
                                $res->user_id = $user_id;
                                $res->txn_id = $txn_id;
                                $res->txn_amount = $final_amount;
                                $res->txn_status = 'success';
                                $res->txn_type = 'debit';
                                $msg = "payment done to order";
                                $res->comment=$msg;

                                //$res->save();
                                if($res->save()){
                                //update user wallet
                                $user=User::find($user_id);
                                $user->wallet=$user->wallet-$final_amount;
                                $user->save();

                                $response['status']=true;
                                $response['discount']=$discount;
                                $response['final_amount']=$final_amount;
                                $response['code']=$order_code;
                                $response['payment']="done";
                                $response['msg']="Request Created!";
                                return json_encode($response);

                                }
                                else{
                                    return response()->json(['status'=>false,'error'=>'Something Went Wrong!']);
                                }
                        }
                    }
                }
            }
            
            $response['status']=true;
            $response['discount']=$discount;
            $response['final_amount']=$final_amount;
            $response['code']=$order_code;
            $response['payment']="notdone";
            $response['msg']="Request Created!";

        }
        else
        {
            $response['status']=false;
            $response['msg']="request could not be Created!";
        }

        return json_encode($response);
    }

    public function initiateOrderTransaction(Request $request)
    {
        $validator = Validator::make($request->all(), [ 
            'order_id'=>'required',
        ]);

        if ($validator->fails())
        {
            return response(['errors'=>$validator->errors()->all()], 422);
        }

        $mid=env("PAYTM_MID");
        $key=env("PAYTM_MERCHANT_KEY");
        $website=env("PAYTM_WEBSITE");
        $paytmParams = array(); 

        $order=UserOrders::where('order_code',$request->order_id)->get();
        $order_id=$request->order_id;
        if(count($order)>0)
        {
            $customer=Auth::user()->share_code;
        $paytmParams["body"] = array(
        "requestType"   => "Payment",
        "mid"           => $mid,
        "websiteName"   => $website,
        "orderId"       => $order_id,
        "callbackUrl"   => "https://api.marketpluss.com/VerifyOrderTransaction",
        "txnAmount"     => array(
        "value"     => $order[0]->total_amount,
        "currency"  => "INR",
        ),
        "userInfo"      => array(
        "custId"    => $customer,
        )
        );

        /*
        * Generate checksum by parameters we have in body
        * Find your Merchant Key in your Paytm Dashboard at https://dashboard.paytm.com/next/apikeys 
        */
        $checksum = PaytmChecksum::generateSignature(json_encode($paytmParams["body"], JSON_UNESCAPED_SLASHES), $key);

        $paytmParams["head"] = array(
            "signature"    => $checksum,
            "authenticated"=>true
        );

        $post_data = json_encode($paytmParams, JSON_UNESCAPED_SLASHES);

        if(env("APP_DEBUG")) // condition to check this is beta or release
				{
					/* for Staging */
                    $url = "https://securegw-stage.paytm.in/theia/api/v1/initiateTransaction?mid=$mid&orderId=$order_id";

				}
				else
				{
					$url = "https://securegw.paytm.in/theia/api/v1/initiateTransaction?mid=$mid&orderId=$order_id";

				}

        
        /* for Production */
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json")); 
        $response = curl_exec($ch);
        $response=json_decode($response);

        $res=array();
        // // print_r($response);
        if(isset($response->body->txnToken))
        {
            $res['status']=true;
            $res['txn_token']=$response->body->txnToken;
        }
        else
        {
            $resp['status']=false;
            $resp['msg']="invalid request, try again";
        }
        }
        else
        {
            $res['status']=false;
            $res['msg']="invalid Order id.";
        }

        return json_encode($res,JSON_UNESCAPED_SLASHES); 
    }

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
             ->where('vendors.status','Active')->where('payment_accept',1)
             ->selectRaw("{$haversine} AS distance")
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
}
