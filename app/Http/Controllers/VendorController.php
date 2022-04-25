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
class VendorController extends Controller
{
    public function get_vendor_data(Request $request)
    {
        $vendor_id=Auth::user()->id;
		$response['current_rating']=Auth::user()->current_rating;
        $response['shop_visit']=Vendor_Shop_Visit::where('vendor_id',$vendor_id)->where('user_activity','shop_visit')->count();
        $response['contact']=Vendor_Shop_Visit::where('vendor_id',$vendor_id)->where('user_activity','contact')->count();

        $response['followers']=Vendors_Subsciber::where('vendor_id',$vendor_id)->count();
        $response['total_earnning']=UserOrders::where('vendor_id',$vendor_id)->sum('order_amount');

        $response['feed_save']=Feed_Save::whereIn('feed_id', function($q) use($vendor_id){
            $q->from('feeds')->where('vendor_id',$vendor_id)->where('user_type','vendor')->selectRaw('id');
        })->count();

        $response['feed_view']=Feed::where('vendor_id',$vendor_id)->where('user_type','vendor')->sum('feed_view');

        $res['status']=true;
        $res['data']=$response;
        return json_encode($res);
    }
    public function get_orders_vendor(Request $request)
    {
        $vendor_id=Auth::user()->id;
        $data=UserOrders::with('user')->where('vendor_id',$vendor_id)->orderByDesc('id')->paginate(20);;
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

    public function get_orders_details_vendor(Request $request)
    {
        $validator = Validator::make($request->all(), [ 
            'order_code' => 'required', 
        ]);
		//return Auth::user()->id;

		if ($validator->fails())
    	{
        	return response(['errors'=>$validator->errors()->all()], 422);
    	}

        $data=UserOrders::with('user')->where('vendor_id',Auth::user()->id)->where('order_code',$request->order_code)->orderByDesc('id')->get();
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
    public function verify_order_id(Request $request)
    {
        $validator = Validator::make($request->all(), [ 
            'order_id' => 'required'
        ]);
        if ($validator->fails())
        {
            return response(['errors'=>$validator->errors()->all()], 422);
        }
        $vendor_id=Auth::user()->id;
        $data=UserOrders::with('user')->where('order_code',$request->order_id)->where('vendor_id',$vendor_id)->where('order_status','pending')->get();
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

    public function update_order_status(Request $request)
    {
        $validator = Validator::make($request->all(), [ 
            'order_id' => 'required',
            'status' => 'required',
            'message' => 'required_if:status,!=,declined'
        ]);
        if ($validator->fails())
        {
            return response(['errors'=>$validator->errors()->all()], 422);
        }
        $vendor_id=Auth::user()->id;

        $data=UserOrders::where('order_code',$request->order_id)->where('vendor_id',$vendor_id)->update(['order_status'=>$request->status]);
        
        if($data)
        {
            $response['status']=true;

            $order_data=UserOrders::where('order_code',$request->order_id)->get(['user_id']);

             //send notification to vendor
             $heading_user= "Your Order ".$request->order_id." has been ".$request->status;
             $post_url=env('NOTIFICATION_USER_URL')."/ViewOrder/".$request->order_id;
             ProcessPush::dispatch($heading_user,$post_url,$vendor_id,'user','');

            // $heading_user = "Order has been accepted!";
        }
        else if($request->status != 'accept')
        {
            $response['status']=false;
            $response['msg']=$request->message;
            // $heading_user = $request->message;
        }

        //notification details 
        // $post_url=env('NOTIFICATION_USER_URL')."/offer/".$maxid;
        // $desc = $request->offer_description;
        
        // //insert notification
        // ProcessPush::dispatch($heading_user,$post_url,$subscriber,"user",$desc);
        return json_encode($response,JSON_UNESCAPED_SLASHES);
    }

    


    //Vendor Shop Visit
    public function vendor_shop_visit()
    {
        $id = Auth::user()->id;
        $data = Vendor_Shop_Visit::join('vendors','vendors.id','vendor_shop_visit.vendor_id')
        ->join('users','users.id','vendor_shop_visit.user_id')
        ->where('vendor_shop_visit.vendor_id',$id)
        ->where('vendor_shop_visit.user_activity','shop_visit')
        ->select(['users.name','users.profile_pic','vendor_shop_visit.created_at as time'])
        ->paginate(10);
        if(count($data)>0){
            $response['status']=true;
            $response['data']=$data;
        }else{
            $response['status']=false;
            $response['msg']="No data found!";
        }

        return response()->json($response);
    }
    //Contact Details 
    public function get_contacts_detail()
    {
        $id = Auth::user()->id;
        $data = Vendor_Shop_Visit::join('vendors','vendors.id','vendor_shop_visit.vendor_id')
        ->join('users','users.id','vendor_shop_visit.user_id')
        ->where('vendor_shop_visit.vendor_id',$id)
        ->where('vendor_shop_visit.user_activity','contact')
        ->select(['users.name','users.profile_pic','vendor_shop_visit.created_at as time'])
        ->paginate(10);
        if(count($data)>0){
            $response['status']=true;
            $response['data']=$data;
        }else{
            $response['status']=false;
            $response['msg']="No data found!";
        }

        return response()->json($response);
    }

    //Saved Feed User Details
    public function get_saved_feed_user_detail()
    {
        $id = Auth::user()->id;
        $data = Feed::join('feed_saves','feed_saves.feed_id','feeds.id')
        ->where('feeds.vendor_id',$id)
        ->select(['feeds.id as feed_id'])
        ->addSelect(['username' => User::select('name')->whereColumn('id', 'feed_saves.user_id')])
        ->addSelect(['profile_pic' => User::select('profile_pic')->whereColumn('id', 'feed_saves.user_id')])
        ->paginate(10);
        if(count($data)>0){
            $response['status']=true;
            $response['data']=$data;
        }else{
            $response['status']=false;
            $response['msg']="No data found!";
        }

        return response()->json($response);
    }
    //Vendor Feed View
    public function get_vendor_follower()
    {
        $id = Auth::user()->id;
        $data = Vendors_Subsciber::join('vendors','vendors.id','vendors_subscibers.vendor_id')
        ->join('users','users.id','vendors_subscibers.user_id')
        ->where('vendors_subscibers.vendor_id',$id)
        ->select(['users.name','users.profile_pic'])
        ->paginate(10);
        if(count($data)>0){
            $response['status']=true;
            $response['data']=$data;
        }else{
            $response['status']=false;
            $response['msg']="No data found!";
        }

        return response()->json($response);
    }
	public function edit_category(Request $request){
	    $validator = Validator::make($request->all(), [ 
	       'category_id'=> 'required',
		   'name'=>'required'
        ]);
		 
		if ($validator->fails())
        {
           return response(['errors'=>$validator->errors()->all()], 422);
        }
		
		$vendor_id=Auth::user()->id;
		//return $vendor_id;
		
		$res = Vendor_category::find($request->category_id);
		if($res->vendor_id == $vendor_id){
			$res->name = $request->name;
			if($res->save()){
				$response['status']=true;
                $response['msg']="Category Updated!";
			}else{
				$response['status']=false;
                $response['msg']="Something went wrong!";
			}
		}else{
            $response['status']=false;
            $response['msg']="You don't have access to perforn this action!";
		}
		
    return json_encode($response,JSON_UNESCAPED_SLASHES);
	}
	
	
	
	
	public function get_vendor_data_using_code(Request $request)
	{
		$validator = Validator::make($request->all(), [ 
             'vendor_code'=> 'required'
         ]);
		 
		  if ($validator->fails())
         {
             return response(['errors'=>$validator->errors()->all()], 422);
         }
		 
		 $data=Vendor::where('vendor_code',$request->vendor_code)->get();
		 
		 if(count($data)>0)
		 {
			 $response['data']=$data;
			 $response['status']=true;
		 }
		 else
		 {
			 $response['msg']="InvalidCode";
			 $response['status']=false;
		 }
		 return json_encode($response,JSON_UNESCAPED_SLASHES);
	}
	
	//get store timing
	
	public function update_store_timing(Request $request)
	{
		$validator = Validator::make($request->all(), [ 
             'days'=> 'required|array'
         ]);
 
         if ($validator->fails())
         {
             return response(['errors'=>$validator->errors()->all()], 422);
         }
		 $vendor_id=Auth::user()->id;
		$del= vendor_timing::where('vendor_id',$vendor_id)->delete();
		
		$data=array();
		foreach($request->days as $key => $d)
		{
			$data[]=['vendor_id'=>$vendor_id,'day_status'=>$d['status'], 'day_name'=> $d['day_name'], 'open_timing'=> date("H:i:s",strtotime($d['open'])) , 'close_timing'=> date("H:i:s",strtotime($d['close']))]; 
		}
		 
		 if(vendor_timing::insert($data))
        {
            $response['status']=true;
            $response['msg']="Timing Successfully Updated!";
        }
        else{
            $response['status']=false;
            $response['msg']="Timing could not be updated!";
        }
		
			return json_encode($response,JSON_UNESCAPED_SLASHES);
			
	}
	
	
	//get vendor notifications
	
	public function fetch_vendor_notification(Request $request)
	{
		$user_id=Auth::user()->id;
		$notifications =Notification::join('vendors','vendors.id','notifications.received_id')->where('received_id',$user_id)->where('receiver_type','vendor')->orderBy('notifications.id', 'DESC')->paginate(10);
		
		$response['status']=true;
		$response['data']=$notifications;
		
		return json_encode($response,JSON_UNESCAPED_SLASHES);
	}

      //get user profile 
      public function get_vendor_profile(Request $request)
      {
          $user_id=Auth::user()->id;
          $user=Vendor::with('timings')->where('id',$user_id)->get();
        
         
          if(count($user)>0)
          {
              $response['status']=true;
              $response['data']=$user;
              $x=0;
              if($user[0]->profile_pic == null)
              {
                  $x=$x+1;
                  $response['data'][0]['step']=$x;
              }

              $cover=Vendor_cover::where('vendor_id',$user_id)->get();

            if(count($cover)==0)
            {
                $x=$x+1;
                $response['data'][0]['step']=$x;
            }
    
          }
          else{
              $response['status']=false;
              $response['msg']="Invalid token";
          }
  
          return json_encode($response);
      }


     //function for update profile of user
     public function update_profile_vendor(Request $request)
     {
         $validator = Validator::make($request->all(), [ 
             'name' => 'required', 
             'email' => 'nullable|email',
             //'shop_name'=>'required',
			 'description'=>'required'
         ]);
 
         if ($validator->fails())
         {
             return response(['errors'=>$validator->errors()->all()], 422);
         }
         
        $vendor_id=Auth::user()->id;
		
		if(isset($request->update_type))
		{
			$str_result = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890abcdefghijklmnopqrstuvwxyz'; 
			
			$name=substr(str_replace(' ', '',$request->name), 0, 4); 
			//return $name;
			$rand= substr(str_shuffle($str_result), 0, 6); 
			$user->share_code =strtoupper($name.$rand);
		}
		
         $user=vendor::find($vendor_id);
         $user->name=$request->name;
         $user->email=$request->email;
         $user->shop_name=$request->name;
		 $user->description=$request->description;

         $user->whatsapp=$request->whatsapp;
         $user->website=$request->website;
         if($user->save())
         {
             $response['status']=true;
             $response['msg']="Profile successfully updated!";
         }
         else{
             $response['status']=false;
             $response['msg']="Profile could not be updated!";
         }
         
         echo json_encode($response);
     }
 
 //function for call profile pic
     public function update_profile_picture_vendor(Request $request)
     {
         $validator = Validator::make($request->all(), [ 
             'update_profile_picture'=> 'required|image|mimes:jpeg,png,jpg,gif,webp,svg'
         ]);
 
         if ($validator->fails())
         {
             return response(['errors'=>$validator->errors()->all()], 422);
         }
         
         //condition to check iF file exits or not
         if($request->hasFile('update_profile_picture'))
         {
            $pic=$request->file('update_profile_picture');
			
			// $current_pic=Auth::user()->profile_pic;
			
			// //code for delete the file from storage
			// $nf= str_replace(env('APP_CDN_URL'),'',$current_pic);
			// Storage::disk(env('DEFAULT_STORAGE'))->delete($nf);
			
			//object to upload the file
			$globalclass=new GlobalController();

            //Remove Previous Image
            $globalclass->removeprevious();
            
			$path="shop_pic/";
			
			$res=$globalclass->upload_img($pic,$path);
			   
             if(!$res['status'])
             {
                  $response['status']=false;
                 $response['msg']="Profile could not be updated!";
             }
             else{
				 $name=$res['file_name'];
				 $vendor_id=Auth::user()->id;
                $user=vendor::find($vendor_id);
                $user->profile_pic=$res['file_name'];
                
                if($user->save())
                {
                    $response['status']=true;
                    $response['profile_pic']=$res['file_name'];
                }
                else
                {
                    $response['status']=false;
                    $response['msg']="Profile could not be updated!";
                }
            }
      
         }
         else{
             $response['status']=false;
             $response['msg']="Invalid File";
         }
 
         echo json_encode($response,JSON_UNESCAPED_SLASHES);
     }


     //function for call cover pictures
     public function update_cover_vendor(Request $request)
     {
         $validator = Validator::make($request->all(), [ 
             'cover_picture'=> 'required|image|mimes:jpeg,png,jpg,gif,webp,svg|max:2048'
         ]);
 
         if ($validator->fails())
         {
             return response(['errors'=>$validator->errors()->all()], 422);
         }
         
         //condition to check iF file exits or not
         if($request->hasFile('cover_picture'))
         {
             $pic=$request->file('cover_picture');
 
             $globalclass=new GlobalController();
			$path="shop_pic/";
			
			$res=$globalclass->upload_img($pic,$path);
			
             if($res['status'])
             {	
				$name=$res['file_name'];
                $vendor_id=Auth::user()->id;
                $user= new vendor_cover;
                $user->image=$name;
                $user->vendor_id=$vendor_id;
                $user->status='active';

                if($user->save())
                {
                    $response['status']=true;
                    $response['profile_pic']=$name;
                }
                else
                {
                    $response['status']=false;
                    $response['msg']="Profile could not be updated!";
                }
             }
             else{
                 $response['status']=false;
                 $response['msg']="Profile could not be updated!";
             }
      
         }
         else{
             $response['status']=false;
             $response['msg']="Invalid File";
         }
 
         echo json_encode($response,JSON_UNESCAPED_SLASHES);
     }
	 
	 	//get cover vendorss 
	public  function get_cover_vendor(Request $request)
	{
		$vendor_id=Auth::user()->id;
		$response['covers']=Vendor_cover::where('vendor_id',$vendor_id)->get();
		
		$response['status']=true;
		return json_encode($response);
	}
    


     //function for update the vendor category

     public function update_main_category_vendor(Request $request)
     {
        $validator = Validator::make($request->all(), [ 
            'category_id'=> 'required'
        ]);

        if ($validator->fails())
        {
            return response(['errors'=>$validator->errors()->all()], 422);
        }
        $data=array();
        $vendor_id=Auth::user()->id;

       if(vendor_main_categories::where('vendor_id',$vendor_id)->delete())
       {
        foreach($request->category_id as $cat)
        {
              $data[] = ['vendor_id'=>$vendor_id, 'category_id'=> $cat];
        }

        if(vendor_main_categories::insert($data))
        {
            $response['status']=true;
            $response['msg']="Category Successfully Updated!";
        }
        else{
            $response['status']=false;
            $response['msg']="Category could not be updated!";
        }
        }
        else{
            foreach($request->category_id as $cat)
        {
              $data[] = ['vendor_id'=>$vendor_id, 'category_id'=> $cat];
        }

        if(vendor_main_categories::insert($data))
        {
            $response['status']=true;
            $response['msg']="Category Successfully Updated!";
        }
        else{
            $response['status']=false;
            $response['msg']="Category could not be updated!";
        }
        }
        return json_encode($response);
      
    }

    //function for update the product or packages status
    public function update_product_status(Request $request)
     {
        $validator = Validator::make($request->all(), [ 
            'product_id'=> 'required',
            'product_status'=>'required'
        ]);

        if ($validator->fails())
        {
            return response(['errors'=>$validator->errors()->all()], 422);
        }
        $data=array();
        $vendor_id=Auth::user()->id;

        $pp=Vendor_Product::find($required->product_id);

        $pp->status=$request->product_status;

        if($pp->save())
        {
       
            $response['status']=true;
            $response['msg']="Successfully Updated!";
        }
        else{
            $response['status']=false;
            $response['msg']=" could not be updated!";
        }
        
        return json_encode($response);
      
    }

    public function create_category_vendor(Request $request)
    {
        $validator = Validator::make($request->all(), [ 
            'category_name'=> 'required',
            'status'=> 'required'
        ]);

        if ($validator->fails())
        {
            return response(['errors'=>$validator->errors()->all()], 422);
        }
        
        $vendor_id=Auth::user()->id;

        $category = new Vendor_category;

        $category->vendor_id=$vendor_id;
        $category->name=$request->category_name;
        $category->status=$request->status;

        if($category->save())
        {
            $response['status']=true;
            $response['msg']="Category Successfully Updated!";
        }
        else{
            $response['status']=false;
            $response['msg']="Category could not be updated!";
        }
        return json_encode($response);
    }


    public function update_category_vendor(Request $request)
    {
        $validator = Validator::make($request->all(), [ 
            'category_name'=> 'required',
            'category_status'=> 'required',
            'category_id'=>'required'
        ]);

        if ($validator->fails())
        {
            return response(['errors'=>$validator->errors()->all()], 422);
        }
        
        $category_id=$request->category_id;

        $category = Vendor_category::find($category_id);

        $category->name=$request->category_name;
        $category->status=$request->category_status;

        if($category->save())
        {
            $response['status']=true;
            $response['msg']="Category Successfully Updated!";
        }
        else{
            $response['status']=false;
            $response['msg']="Category could not be updated!";
        }
        return json_encode($response);
    }

    public function update_store_location(Request $request)
    {
        $validator = Validator::make($request->all(), [ 
            'latitude'=> 'required',
            'longitude'=> 'required',
            'area'=> 'required',
            'city'=> 'required',
            'state'=> 'required',
            'address'=> 'required',
            'pincode'=> 'required',
        ]);

        if ($validator->fails())
        {
            return response(['errors'=>$validator->errors()->all()], 422);
        }
        
        $vendor_id=Auth::user()->id;

        $vendor=Vendor::find($vendor_id);

        $vendor->city=$request->city;
        $vendor->area=$request->area;
        $vendor->state=$request->state;
        $vendor->address=$request->address;
        $vendor->shop_latitude=$request->latitude;
        $vendor->shop_longitude=$request->longitude;
        $vendor->pincode=$request->pincode;
        $vendor->shop_no=$request->shop_no;
        if($vendor->save())
        {
            $response['status']=true;
            $response['msg']="Address Updated!";
        }
        else{
            $response['status']=false;
            $response['msg']="Address could not be updated!";
        }
        return json_encode($response);
    }


    //for add the product or services


    public function vendor_add_product(Request $request)
    {
		//return $request;
        $validator = Validator::make($request->all(), [ 
            'product_name'=> 'required',
            'vendor_category_id'=> 'required',
            'market_price'=> 'required',
            'price'=> 'required',
            'product_img'=> 'required',
            'type'=>'required'
        ]);

        if ($validator->fails())
        {
            return response(['errors'=>$validator->errors()->all()], 422);
        }
        
        $vendor_id=Auth::user()->id;

         //condition to check iF file exits or not
         if($request->hasFile('product_img'))
         {
             $pic=$request->file('product_img');
             $path="products/";
				
			$globalclass=new GlobalController();
			
			$res=$globalclass->upload_img($pic,$path);
			
             if($res['status'])
             {
                $path=$res['file_name'];

                $v_product=new Vendor_Product;
                $v_product->product_name=$request->product_name;
                $v_product->market_price=$request->market_price;
                $v_product->our_price=$request->price;
                $v_product->description=$request->description;
                $v_product->status='active';
                $v_product->vendor_id=$vendor_id;
                $v_product->vendor_category_id=$request->vendor_category_id;
                $v_product->product_img=$path;
                $v_product->type=$request->type;
                if($v_product->save())
                {
                    $response['status']=true;
                    $response['msg']="Product Added!";
                }
                else
                {
                    $response['status']=false;
                    $response['msg']="Product could not be Added!";
                }
             }
             else{
                 $response['status']=false;
                 $response['msg']="img could not be updated!";
             }
      
         }
         else{
             $response['status']=false;
             $response['msg']="Invalid File";
         }
         return json_encode($response);
    }
	
	//get_selected_category_vendor
	
	public function get_selected_category_vendor(Request $request)
	{
		$vendor_id=Auth::user()->id;
		
		$cat=Category::whereIn('id',function($q)use($vendor_id){
			$q->from('vendor_main_categories')->select('category_id')->where('vendor_id',$vendor_id);
		})->get();
		
		$response['status']=true;
		$response['data']=$cat;
		return json_encode($response,JSON_UNESCAPED_SLASHES);
	}
	
    //update vendor servicess

    public function vendor_update_product(Request $request)
    {
        $validator = Validator::make($request->all(), [ 
            'product_name'=> 'required',
            'vendor_category_id'=> 'required',
            'market_price'=> 'required',
            'price'=> 'required',
            'product_img'=> 'required',
            'product_id'=>'required',
            'type'=>'required'
        ]);

        if ($validator->fails())
        {
            return response(['errors'=>$validator->errors()->all()], 422);
        }
        
        $vendor_id=Auth::user()->id;

         //condition to check iF file exits or not
         if($request->hasFile('product_img'))
         {
             $pic=$request->file('product_img');
             $path="products/";
 
              $globalclass=new GlobalController();
		
			
			$res=$globalclass->upload_img($pic,$path);
			
             if($res['status'])
             {
                $current_pic=Vendor_Product::find($request->product_id);
                //code for delete the file from storage
                $nf= str_replace(env('APP_CDN_URL'),'',$current_pic->product_img);
                Storage::disk(env('DEFAULT_STORAGE'))->delete($nf);

                $path=$res['file_name'];

                $v_product= Vendor_Product::find($request->product_id);
                $v_product->product_name=$request->product_name;
                $v_product->market_price=$request->market_price;
                $v_product->our_price=$request->price;
                $v_product->description=$request->description;
                $v_product->status='active';
                $v_product->vendor_id=$vendor_id;
                $v_product->vendor_category_id=$request->vendor_category_id;
                $v_product->product_img=$path;
                $v_product->type=$request->type;
                
                if($v_product->save())
                {
                    $response['status']=true;
                    $response['msg']="Product Updated!";
                }
                else
                {
                    $response['status']=false;
                    $response['msg']="Product could not be Added!";
                }
             }
             else{
                 $response['status']=false;
                 $response['msg']="img could not be updated!";
             }
      
         }
         else{
            $v_product= Vendor_Product::find($request->product_id);
            $v_product->product_name=$request->product_name;
            $v_product->market_price=$request->product_market_price;
            $v_product->our_price=$request->price;
            $v_product->description=$request->description;
            $v_product->status='active';
            $v_product->vendor_id=$vendor_id;
            $v_product->vendor_category_id=$request->vendor_category_id;
            $v_product->type=$request->type;
            
            if($v_product->save())
            {
                $response['status']=true;
                $response['msg']="Product Added!";
            }
            else
            {
                $response['status']=false;
                $response['msg']="Product could not be Added!";
            }
         }
         return json_encode($response);
    }


    public function add_vendor_offer(Request $request)
    {
		//return $request;
		//return Auth::user()->id;
        $validator = Validator::make($request->all(), [ 
            'offer_name'=> 'required',
            // 'offer_type'=> 'required',
            'offer'=> 'required',
            'start_date'=> 'required',
            'end_date'=>'required',
           // 'vendor_id'=>'required',
			'offer_description'=>'required'
        ]);
        if ($validator->fails())
        {
            return response(['errors'=>$validator->errors()->all()], 422);
        }
        
        $offer=new Vendor_Offer;
        $vendor_id=Auth::user()->id;
        $offer->offer_name=$request->offer_name;
        // $offer->offer_type=$request->offer_type;
        $offer->offer=$request->offer;
        $offer->start_from=date("Y-m-d",strtotime($request->start_date));
        $offer->start_to =date("Y-m-d",strtotime($request->end_date));
        $offer->status ='active';
        $offer->vendor_id= $vendor_id;
		$offer->offer_description= "hi";
		$offer->offer_description= $request->offer_description;
        if($offer->save())
        {
            $offer_id=$offer->id;
            $data=array();
            foreach($request->products as $pp)
            {
                $data[]=["offer_id"=>$offer_id,"product_id"=>$pp];
            }

            foreach($request->packages as $pp)
            {
                $data[]=["offer_id"=>$offer_id,"product_id"=>$pp];
            }
			 

            if(Vendor_Offer_Product::insert($data))
            {
                $response['status']=true;
                $response['msg']="Offer Added!";
				$maxid = Vendor_Offer_Product::max('offer_id');
                $subscriber = Vendors_Subsciber::where('vendor_id',Auth::user()->id)->get(['user_id'])->toArray();
				
				
				//notification details 
				$heading_user= Auth::user()->name." has created an offer.";
				$post_url=env('NOTIFICATION_USER_URL')."/offer/".$maxid;
				$desc = $request->offer_description;
				
				//insert notification
				ProcessPush::dispatch($heading_user,$post_url,$subscriber,"user",$desc);
				
            }
            else
            {
                $response['status']=false;
                $response['msg']="Offer could not be Added!";
            }
        }
        else{
                $response['status']=false;
                $response['msg']="Offer could not be Added!";
            
        }


        return json_encode($response);
    }


    public function update_vendor_offer(Request $request)
    {
		//return $request;
        $validator = Validator::make($request->all(), [ 
            'offer_name'=> 'required',
            //'offer_type'=> 'required',
            'offer'=> 'required',
            'start_date'=> 'required',
            'end_date'=>'required',
            'vendor_id'=>'required',
            'offer_id'=>'required',
			'offer_description'=>'required'
        ]);

        if ($validator->fails())
        {
            return response(['errors'=>$validator->errors()->all()], 422);
        }
        
        $offer=Vendor_Offer::find($request->offer_id);
        $offer->offer_name=$request->offer_name;
        $offer->offer_type=$request->offer_type;
        $offer->offer=$request->offer;
        $offer->start_from=$request->start_date;
        $offer->start_to =$request->end_date;
       // $offer->status =$request->offer_name;
		$offer->offer_description= $request->offer_description;
        if($offer->save())
        {
            if(Vendor_Offer_Product::where('offer_id',$request->offer_id)->delete())
            {
                $offer_id=$request->offer_id;
                $data=array();
                foreach($request->products as $pp)
                {
                    $data[]=["offer_id"=>$offer_id,"product_id"=>$pp];
                }

                foreach($request->packages as $pp)
                {
                    $data[]=["offer_id"=>$offer_id,"product_id"=>$pp];
                }

                if(Vendor_Offer_Product::insert($data))
                {
                    $response['status']=true;
                    $response['msg']="Offer Updated!";
                }
                else
                {
                    $response['status']=false;
                    $response['msg']="Offer could not be Updated!";
                }
            }
            else{
                $offer_id=$request->offer_id;
                $data=array();
                foreach($request->products as $pp)
                {
                    $data[]=["offer_id"=>$offer_id,"product_id"=>$pp];
                }

                foreach($request->packages as $pp)
                {
                    $data[]=["offer_id"=>$offer_id,"product_id"=>$pp];
                }

                if(Vendor_Offer_Product::insert($data))
                {
                    $response['status']=true;
                    $response['msg']="Offer Updated!";
                }
                else
                {
                    $response['status']=false;
                    $response['msg']="Offer could not be Updated!";
                }
            }
        }
        else{
                $response['status']=false;
                $response['msg']="Offer could not be Updated!";
            
        }


        return json_encode($response);
    }


    public function get_category_vendor(Request $request)
    {
		//return "Hello";
        $validator = Validator::make($request->all(), [ 
            'vendor_id'=>'required'
        ]);

        if ($validator->fails())
        {
            return response(['errors'=>$validator->errors()->all()], 422);
        }
        
        
        $cat= Vendor_category::where('status','active')->where('vendor_id',$request->vendor_id)->get();

        if(sizeof($cat)>0)
        {
            $response['status']=true;
                $response['data']=$cat;
            }
            else
            {
                $response['status']=false;
                $response['msg']="No Data Found";
            }
            return json_encode($response);
    }
	
	
	
	
	
    public function get_vendor_details(Request $request)
    {
        $validator = Validator::make($request->all(), [ 
            'vendor_id' => 'required',
            'latitude'=>'required',
            'longitude'=>'required'
        ]);

		if ($validator->fails())
    	{
        	return response(['errors'=>$validator->errors()->all()], 422);
    	}

       
		$user_id=Auth::user()->id;
		$haversine = "(6371 * acos(cos(radians(" . $request->latitude . ")) 
        * cos(radians(`shop_latitude`)) 
        * cos(radians(`shop_longitude`) 
        - radians(" . $request->longitude . ")) 
        + sin(radians(" . $request->latitude . ")) 
        * sin(radians(`shop_latitude`))))";
		
		 //fetch store details of vendor
        //  return $request->vendor_id;
        $store_data=Vendor::with('covers')->with('shop_timing')->with('today_timing')->where('vendors.status','active')->where('id','=',$request->vendor_id)
        ->addSelect([
        'category_id'=>Category::select('id')->where('parent_id','0')->whereIn('id',vendor_main_categories::select('category_id')->where('vendor_id',$request->vendor_id)),
        'vendor_follow' =>Vendors_Subsciber::select('vendor_id')->whereColumn('vendor_id', 'vendors.id')->where('user_id',$user_id)])
        ->selectRaw("{$haversine} AS distance")->get();
        
        if(count($store_data)>0)
        {
            $response['status']=true;
            $response['data']=$store_data;
            //$response['distance']=$distance; 
			$response['categories']=Vendor_category::with('products')->where('vendor_id',$request->vendor_id)->get();
            // $response['shop_timing']=vendor_timing::where('vendor_id',$request->vendor_id)->where('day_status','1')->get(['day_name','open_timing','close_timing']);
            $response['data'][0]['followers']=Vendors_Subsciber::where('vendor_id',$request->vendor_id)->count();
        }
        else{
            $response['status']=false;
            $response['msg']="Invalid Vendor Id, Try Again.";
        }

        echo json_encode($response,JSON_UNESCAPED_SLASHES); 
    }

	
	// update status product or offer
	public function update_status_product_offer(Request $request)
	{
		$validator = Validator::make($request->all(), [ 
            'action_id'=> 'required',
            'type'=> 'required',
            'status'=> 'required',
        ]);

        if ($validator->fails())
        {
            return response(['errors'=>$validator->errors()->all()], 422);
        }
        
		
		if($request->type == 'product')
		{
			 $v_product= Vendor_Product::find($request->action_id);
                
                $v_product->status=$request->status;
                
                if($v_product->save())
                {
                    $response['status']=true;
                    $response['msg']="Product updated!";
                }
                else
                {
                    $response['status']=false;
                    $response['msg']="Product could not be updated!";
                }
		}else if($request->type == 'package')
		{
			 $v_product= Vendor_Product::find($request->action_id);
                
                $v_product->status=$request->status;
                
                if($v_product->save())
                {
                    $response['status']=true;
                    $response['msg']="Product updated!";
                }
                else
                {
                    $response['status']=false;
                    $response['msg']="Product could not be updated!";
                }
		}
		else if($request->type == 'offer')
		{
			$offer=Vendor_Offer::find($request->action_id);
        
			$offer->status =$request->status;
			if($offer->save())
			{
				$response['status']=true;
				$response['msg']="updated!";
			}
			else{
				$response['status']=false;
				$response['msg']="Not updated!";
			}
		}
		else
		{
			$response['status']=false;
            $response['msg']="Invalid Request";
		}
		echo json_encode($response,JSON_UNESCAPED_SLASHES);
	}
	
    public function get_vendor_product(Request $request)
    {
		//return $request;
        $validator = Validator::make($request->all(), [ 
            'vendor_category_id' => 'required',
            'product_type'=>'required'
        ]);

		if ($validator->fails())
    	{
        	return response(['errors'=>$validator->errors()->all()], 422);
    	}
        //return $request_category_id;
        if($request->vendor_category_id != 0 && $request->product_type == 'product')
        {
             //fetch store details of vendor
             $store_data=Vendor_Product::with('favourite')->where('vendor_category_id',$request->vendor_category_id)->whereIn('status',['active','inactive'])->get();
        }
        else if($request->vendor_category_id == 0 && $request->product_type == 'product')
        {    
            //fetch store details of vendor
            $store_data=Vendor_Product::with('favourite')->where('type',$request->product_type)->whereIn('status',['active','inactive'])->get();
        }
        else if($request->vendor_category_id != 0 && $request->product_type == 'package')
        {    
            //fetch store details of vendor
            $store_data=Vendor_Product::with('favourite')->where('type',$request->product_type)->whereIn('status',['active','inactive'])->get();
        }
        else
        {    
            //fetch store details of vendor
            $store_data=Vendor_Product::with('favourite')->where('type',$request->product_type)->whereIn('status',['active','inactive'])->get();
        }
        
        
         // echo $store_data;
         // exit;
         if(count($store_data)>0)
         {
             $response['status']=true;
             $response['data']=$store_data;
         }
         else{
             $response['status']=false;
             $response['msg']="Invalid Category, Try Again.";
         }
 
         echo json_encode($response,JSON_UNESCAPED_SLASHES);
    }
	
	
	 public function get_vendor_product_vendor(Request $request)
    {
		//return $request;
        $validator = Validator::make($request->all(), [ 
            'vendor_category_id' => 'required',
            'product_type'=>'required'
        ]);

		if ($validator->fails())
    	{
        	return response(['errors'=>$validator->errors()->all()], 422);
    	}
		
		 $vendor_id=Auth::user()->id;
		 $type=$request->product_type;
		//return $vendor_id;
        //return $request_category_id;
        if($request->vendor_category_id != 0 )
        {
             //fetch store details of vendor
             $store_data=Vendor_Product::where('vendor_category_id',$request->vendor_category_id)->where('type',$request->product_type)->where('vendor_id',$vendor_id)->where('status','!=','delete')->get();
        }
       
        else
        {    
            //fetch store details of vendor
            $store_data=Vendor_Product::where('type',$request->product_type)->where('vendor_id',$vendor_id)->where('status','!=','delete')->get();
        }
        
        
         // echo $store_data;
         // exit;
         if(count($store_data)>0)
         {
             $response['status']=true;
             $response['data']=$store_data;
         }
         else{
             $response['status']=false;
             $response['msg']="Invalid Category, Try Again.";
         }
 
         echo json_encode($response,JSON_UNESCAPED_SLASHES);
    }

    public function get_vendor_offers(Request $request)
    {
        $validator = Validator::make($request->all(), [ 
            'vendor_id'=>'required',
            'latitude'=>'required',
            'longitude' => 'required',
            'category_id' =>'required'
        ]);
		
		
        if ($validator->fails())
        {
            return response(['errors'=>$validator->errors()->all()], 422);
        }

        $vendor_id=$request->vendor_id;

        $haversine = "(6371 * acos(cos(radians(" . $request->latitude . ")) 
        * cos(radians(`shop_latitude`)) 
        * cos(radians(`shop_longitude`) 
        - radians(" . $request->longitude . ")) 
        + sin(radians(" . $request->latitude . ")) 
        * sin(radians(`shop_latitude`))))";
	
        $day=date("Y-m-d");
		//confitions for check all the users
        if($vendor_id != 0)
        {
			
			$offer_data=Vendor_Offer::with('vendor')->where('status','active')->where('start_from','<=',$day)->where('start_to','>=',date('Y-m-d'))->where('vendor_id',$vendor_id)->paginate(10);

        }
        else{
            if($request->category_id != 0)
            {
               $cate_id=$request->category_id;
               $offer_data=Vendor::where('vendors.status','active')->join('vendor_offers','vendor_offers.vendor_id','vendors.id')
                ->where('start_from','<=',$day)->where('start_to','>=',date('Y-m-d'))
                ->select(['vendors.*','vendor_offers.offer_description','vendor_offers.offer_name','vendor_offers.offer','vendor_offers.start_from','vendor_offers.start_to','vendor_offers.id as offer_id'])
            ->whereIn('vendors.id',function($q) use($cate_id){
                $q->from('vendor_main_categories')->selectRaw('vendor_id')->where('category_id',$cate_id);
            })->where('vendor_offers.status','active')->selectRaw("{$haversine} AS distance")
            ->having('distance','<','25')->orderBy('distance')
            ->paginate(10);

            }
            else{
		
            
                $offer_data=Vendor::where('vendors.status','active')->join('vendor_offers','vendor_offers.vendor_id','vendors.id')
				->where('start_from','<=',$day)->where('start_to','>=',date('Y-m-d'))
				->select(['vendors.*','vendor_offers.offer_description','vendor_offers.offer_name','vendor_offers.offer','vendor_offers.start_from','vendor_offers.start_to','vendor_offers.id as offer_id'])
				->selectRaw("{$haversine} AS distance")
				->having('distance','<','25')
				->where('vendor_offers.status','active')
				->orderBy('distance')
				->paginate(10);
		
            }
			// return $offer_data;
        }
        
        
       foreach($offer_data as $key=>$o)
		{
			$offer_id=$o->offer_id;
			$offer_data[$key]['products']=Vendor_Product::whereIn('id',function($q) use($offer_id){
           
                    $q->from('vendor_offer_products')->selectRaw('product_id')->whereIn('offer_id',[$offer_id]);
                    })->get();
		}
		//return $store_data;
		
        if(count($offer_data)>0)
        {
            $response['status']=true;
            $response['data']=$offer_data;
        }
        else{
            $response['status']=false;
            $response['msg']="Invalid Category, Try Again.";
        }

        echo json_encode($response,JSON_UNESCAPED_SLASHES);
    }
    
	
	
	 public function get_vendor_offers_single(Request $request)
    {
        $validator = Validator::make($request->all(), [ 
            'offer_id'=>'required',
        ]);
		
		
        if ($validator->fails())
        {
            return response(['errors'=>$validator->errors()->all()], 422);
        }

        $offer_id=$request->offer_id;

       
		//confitions for check all the users

		$offer_data=Vendor::join('vendor_offers','vendor_offers.vendor_id','vendors.id')
                    ->whereDate('vendor_offers.start_to','>=',date('Y-m-d'))
                    ->select(['vendors.*','vendor_offers.offer_description','vendor_offers.offer_name','vendor_offers.offer','vendor_offers.start_from','vendor_offers.start_to','vendor_offers.id as offer_id'])
                    ->where('vendor_offers.id',$offer_id)
                    ->where('vendors.status','active')
                    ->where('vendor_offers.status','!=','delete')
                    ->get();
		foreach($offer_data as $key=>$o)
		{
			$offer_id=$o->offer_id;
			$offer_data[$key]['products']=Vendor_Product::whereIn('id',function($q) use($offer_id){
           
                    $q->from('vendor_offer_products')->select('product_id')->where('offer_id',$offer_id);
                    })->get();
		}
		//return $store_data;
		
        if(count($offer_data)>0)
        {
            $response['status']=true;
            $response['data']=$offer_data;
        }
        else{
            $response['status']=false;
            $response['msg']="Invalid Category, Try Again.";
        }

        echo json_encode($response,JSON_UNESCAPED_SLASHES);
    }
	
	
	//delete cover pictures
	//get cover vendorss 
	public  function delete_cover_vendor(Request $request)
	{
		//return $request;
		$validator = Validator::make($request->all(), [ 
            'cover_id'=> 'required',
        ]);

        if ($validator->fails())
        {
            return response(['errors'=>$validator->errors()->all()], 422);
        }
		    $vendor_id=Auth::user()->id;
            
            $current_pic=Vendor_cover::find($request->cover_id);
		    $res=Vendor_cover::where('id',$request->cover_id)->where('vendor_id',Auth::user()->id)->delete();

            if($res)
            {
               
                //code for delete the file from storage
                $nf= str_replace(env('APP_CDN_URL'),'',$current_pic->image);
                Storage::disk(env('DEFAULT_STORAGE'))->delete($nf);
                
                $response['status']=true;
                $response['msg']="delete";
            }
            else{
                $response['status']=false;
                $response['msg']="not permitted";
            }
			
				return json_encode($response);
	}
	
	//fetch vendor_offers
	 public function get_vendor_offers_vendor(Request $request)
    {
		//return "Hello";
		//return $request;
        $vendor_id=Auth::user()->id;
		//return $vendor_id;
		$offer_data=Vendor_Offer::where('vendor_id',$vendor_id)->whereDate('start_to','>=',date('Y-m-d'))->where('status','!=','delete')->get();
		
		foreach($offer_data as $key=>$o)
		{
			$offer_id=$o->id;
			$offer_data[$key]['products']=Vendor_Product::whereIn('id',function($q) use($offer_id){
				 $q->from('vendor_offer_products')->selectRaw('product_id')->whereIn('offer_id',[$offer_id]);
			  })->get();
		}
		
		//return count($offer_data);
        if(count($offer_data)>0)
        {
            $response['status']=true;
            $response['data']=$offer_data;
        }
        else{
            $response['status']=false;
            $response['msg']="Invalid Category, Try Again.";
        }

        echo json_encode($response,JSON_UNESCAPED_SLASHES);
    }
	
	
	
	
    public function update_shop_visit(Request $request)
    {
        $validator = Validator::make($request->all(), [ 
            'vendor_id'=>'required',
            'update_type'=>'required'
        ]);

        if ($validator->fails())
        {
            return response(['errors'=>$validator->errors()->all()], 422);
        }

        $user_id=Auth::user()->id;
        
        $shop_visit = new Vendor_Shop_Visit;

        $shop_visit->user_id=$user_id;
        $shop_visit->vendor_id=$request->vendor_id;
        $shop_visit->user_activity=$request->update_type;
        if($shop_visit->save())
        {
                $response['status']=true;
                $response['msg']="Updated!";
        }
        else{
            $response['status']=false;
            $response['msg']="not updated!";
        }
        return json_encode($response);
    }
    
    public function vendorReviewsRating()
	{
		
		$vendor_id=Auth::user()->id;
		
		$vr=vendor_rating::with('user')->with('vendor')->where('vendor_ratings.vendor_id',$vendor_id)->orderBy('id','DESC')->get();
		
		$total_rating= count($vr);
		//$vr=vendor_rating::selectRaw('*,count(vendor_ratings.*')->where('vendor_ratings.vendor_id',$vendor_id)->get();

		
		$rating_per=vendor_rating::selectRaw('count(vendor_rating)/'.$total_rating.' as percentage,vendor_rating')->where('vendor_id',$vendor_id)->groupBy('vendor_rating')->orderBy('vendor_rating')->get();
		//$vr=vendor_rating::selectRaw('count(*) as cc')->addSelect(['rate1' =>vendor_rating::selectRaw('count(*)/cc')->whereColumn('vendor_id', 'vendor_ratings.vendor_id')->where('vendor_rating',5)])->where('vendor_ratings.vendor_id',$vendor_id)->get();
		
		if(count($vr)>0)
		{
			$response['status']=true;
			$response['data']=$vr;
			$response['data'][0]['rating_percentage']=$rating_per;
		}
		else
		{
			$response['status']=true;
		$response['msg']="No data found.";
		}
		
		return json_encode($response,JSON_UNESCAPED_SLASHES);
	}


   public function update_flat_deals(Request $request)
   {
    $validator = Validator::make($request->all(), [ 
        'first_time' => 'required', 
        'all_time' => 'required',
    ]);

    if ($validator->fails())
    {
        return response(['errors'=>$validator->errors()->all()], 422);
    }
    
   $vendor_id=Auth::user()->id;
   
   
    $user=vendor::find($vendor_id);

    $user->flat_deal_first_time=$request->first_time;
    $user->flat_deal_all_time=$request->all_time;
    if($user->save())
    {
        $response['status']=true;
        $response['msg']="Profile successfully updated!";
    }
    else{
        $response['status']=false;
        $response['msg']="Profile could not be updated!";
    }
    
    echo json_encode($response);
   }
}
