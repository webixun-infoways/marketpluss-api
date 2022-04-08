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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
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

        $vendor_data=Vendor::where('id',$request->vendor_id)->get(['flat_deal_first_time','flat_deal_all_time']);
        $all_discount= $vendor_data[0]['flat_deal_all_time'];

        //create new order
        $discount=floor($request->amount*$all_discount/100);
        $order_amount=$request->amount;
        $final_amount=$request->amount-$discount;
        
        if($discount>0)
        {
            $response['status']=true;
            $response['discount']=$discount;
            $response['final_amount']=$final_amount;

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

        //create new order
        $discount=floor($request->amount*$all_discount/100);
        $order_amount=$request->amount;
        $final_amount=$request->amount-$discount;
        
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


}
