<?php

namespace App\Http\Controllers;
use Mail;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Feed_like;
use App\Models\Feed_Report;
use App\Models\Feed_Save;
use App\Models\Vendor;
use App\Models\Vendor_Product;
use App\Models\Vendors_Subsciber;
use App\Models\Vendor_category;
use App\Models\vendor_rating;
use App\Models\Feed_Comment;
use App\Models\Feed;
use App\Models\Vendor_Offer;
use App\Models\Slider;
use App\Models\Vendor_cover;
use App\Models\Category;
use App\Models\user_product_saves;
use App\Models\refer_earn_setup;
use App\Models\point_level;
use App\Models\user_fev_vendors;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use App\Models\Notification;
use App\Jobs\Processmail;
use App\Jobs\ProcessPush;
use App\Http\Controllers\UserTransactionController;
use App\Helpers\AppHelper;
use App\Models\user_txn_log;
class UserController extends Controller
{
	//fetch front category for user & vendor
    public function send_mail(Request $request)
    {
		// $contact="8006435315";
		// $msg="Use 564434. as your OTP for MarketPluss account verification. This is confidential. Please, do not share this with anyone. Webixun infoways PVT LTD";
		// //return 	AppHelper::send_sms2($contact,$msg);
		// $heading="this is test3";
		// $url="https://webixun.com";
		
		// $user_type="Users";
		// $users=1;
		// return AppHelper::send_Push($heading,$url,$user_type,$users,"khkdhsks");
		return AppHelper::send_Push("sddsdsd","https://marketpluss.com",'user',2,"aajaj");
    }
	
	
	//method for give vendor rating
	
	public function vendor_rating(Request $request)
	{
		$validator = Validator::make($request->all(), [ 
            'rating' => 'required', 
            'vendor_id' => 'required'
        ]);

		if ($validator->fails())
    	{
        	return response(['errors'=>$validator->errors()->all()], 422);
    	}
		
		$vr=new vendor_rating;
		
		$vr->vendor_id=$request->vendor_id;
		$vr->user_id =	Auth::user()->id;
		$vr->vendor_rating =	$request->rating;
		$vr->vendor_review =	$request->review;
		$vr->review_status = 'success';
		
		try{
			$vr->save();
			//Cashback Initiated
			$permission=new UserTransactionController();
			$coin = point_level::get();
			//Point credit to Vendor
	        $permission->credit_coin($request->vendor_id,'12345',$coin[0]->review_point,'Success','UPI');
			//Point credit to User
			$permission->credit_coin(Auth::user()->id,'12345',$coin[0]->review_point,'Success','UPI');
			
			$heading_user= "Cashback Initialted for feed review";
			$heading_vendor= "Your Feed Just Reviewd";
		    $post_url="https://marketpluss.com/";
			//Notification to User
		    ProcessPush::dispatch($heading_user,$post_url,$user_id,'user','');
			//Notification to vendor
			ProcessPush::dispatch($heading_vendor,$post_url,$request->vendor_id,'vendor','');
			
			$response['status']=true;
			$response['msg']="successfully submited";
	
		}
		catch(Exception $e)
		{
			return "Ddd";
		}
		
		return json_encode($response);
	}
	
	
	//user_get_vendor_reviews
	
	public function user_get_vendor_reviews(Request $request)
	{
		$user_id=Auth::user()->id;
		
		$vr=vendor_rating::with('vendor')->where('vendor_ratings.user_id',$user_id)->get();
		
		if(count($vr)>0)
		{
			$response['status']=true;
		$response['data']=$vr;
		}
		else
		{
			$response['status']=true;
		$response['msg']="No data found.";
		}
		
		return json_encode($response,JSON_UNESCAPED_SLASHES);
	}
	
	
	public function get_vendor_reviews(Request $request)
	{
		return $request;
		$validator = Validator::make($request->all(), [ 
            'vendor_id' => 'required'
        ]);

		if ($validator->fails())
    	{
        	return response(['errors'=>$validator->errors()->all()], 422);
    	}
		$vendor_id=$request->vendor_id;
		
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
	
	
	public function get_earn_data()
	{
		$data=refer_earn_setup::all();
		$data2=point_level::all();
		$data['wallet']=Auth::user()->wallet;
		$data['upi']=Auth::user()->upi_id;
		$data['share_code']=Auth::user()->share_code;
		$response['status']=true;
		$response['data']=$data;
		
		$response['data2']=$data2;
		return json_encode($response,JSON_UNESCAPED_SLASHES);
	}
	
	
	public function get_user_transations()
	{
		$user_id=Auth::user()->id;
		
		$data=user_txn_log::where('user_id',$user_id)->orderBy('id','DESC')->get();
		
		if(count($data)>0)
		{
			$response['status']=true;
			$response['data']=$data;
		}
		else
		{
			$response['status']=false;
			$response['msg']="No transactions found";
		}
		
		return json_encode($response);
	}
	
	
	public function fetch_user_notification(Request $request)
	{
		$user_id=Auth::user()->id;
		$notifications =Notification::join('users','users.id','notifications.received_id')->where('received_id',$user_id)->where('receiver_type','user')->orderBy('notifications.id', 'DESC')->paginate(10);
		
		$response['status']=true;
		$response['data']=$notifications;
		
		return json_encode($response,JSON_UNESCAPED_SLASHES);
	}
	
    //fetch front category for user & vendor
    public function get_all_category(Request $request)
    {
       
		
		if(isset($request->category_id))
		{
			 $category= $category=Category::where('parent_id',$request->category_id)->where('status','Active')->get();
		}else{
			 $category=Category::where('status','Active')->where('parent_id',0)->get();
		}
       
       
        $response['data']=$category;

        return json_encode($response,JSON_UNESCAPED_SLASHES);
    }
	
	//add to fevroute
	
	public function user_add_favourite(Request $request)
	{
		 $validator = Validator::make($request->all(), [ 
            'action_id' => 'required', 
            'category_type' => 'required',
			'action_type' => 'required'
        ]);

		if ($validator->fails())
    	{
        	return response(['errors'=>$validator->errors()->all()], 422);
    	}
		
		$user_id=Auth::user()->id;
		
		if($request->category_type == 'product')
		{
			if($request->action_type=='save')
        {
            $feed=new user_product_saves;
            $feed->user_id=Auth::user()->id;
            $feed->product_id=$request->action_id;

            if($feed->save())
        {
            $response['status']=true;
            $response['msg']="Saved";
        }
        else{
            $response['status']=false;
            $response['msg']="Not Updated";
        }
        }
        else if($request->action_type=='unsave'){

            $res=user_product_saves::where('product_id',$request->action_id)->where('user_id',Auth::user()->id)->delete();

            if($res)
            {
                $response['status']=true;
                $response['msg']="UnSaved";
            }
            else{
                $response['status']=false;
                $response['msg']="Not Updated";
            }
        }
        else{
            $response['status']=false;
                $response['msg']="Invalid type";
        }
		}
		else if ($request->category_type == 'shop')
		{
			if($request->action_type=='save')
        {
            $feed=new user_fev_vendors;
            $feed->user_id=Auth::user()->id;
            $feed->vendor_id=$request->action_id;

            if($feed->save())
        {
            $response['status']=true;
            $response['msg']="Saved";
        }
        else{
            $response['status']=false;
            $response['msg']="Not Updated";
        }
        }
        else if($request->action_type=='unsave'){

            $res=user_fev_vendors::where('vendor_id',$request->action_id)->where('user_id',Auth::user()->id)->delete();

            if($res)
            {
                $response['status']=true;
                $response['msg']="UnSaved";
            }
            else{
                $response['status']=false;
                $response['msg']="Not Updated";
            }
        }
        else{
            $response['status']=false;
                $response['msg']="Invalid type";
        }
		}
		else{
			$response['status']=false;
            $response['msg']="Invalid request";
		}
		return json_encode($response);
	}
	
	
	//fetch fev data for the user-
	public function user_get_favourite(Request $request)
	{
		 $validator = Validator::make($request->all(), [ 
            'category_type' => 'required',
        ]);

		if ($validator->fails())
    	{
        	return response(['errors'=>$validator->errors()->all()], 422);
    	}
		
		$user_id=Auth::user()->id;
		
		if($request->category_type == 'product')
		{
			$data=Vendor_Product::whereIn('id',function($q) use($user_id){
				$q->from('user_product_saves')->selectRaw('product_id')->where('user_id',$user_id);
			})->get();
			
			$response['status']=true;
			$response['data']=$data;
		}
		else if ($request->category_type == 'shop')
		{
			$data=Vendor::whereIn('id',function($q) use($user_id){
				$q->from('user_fev_vendors')->selectRaw('vendor_id')->where('user_id',$user_id);
			})->get();
			
			$response['status']=true;
			$response['data']=$data;
		}
		else{
			$response['status']=false;
            $response['msg']="Invalid request";
		}
		return json_encode($response);
	}


	//search api all 
	public function search_all(Request $request)
	{
		 $validator = Validator::make($request->all(), [ 
            'search_query' => 'required',
			'search_type' =>'required'
        ]);

		if ($validator->fails())
    	{
        	return response(['errors'=>$validator->errors()->all()], 422);
    	}
		$q=$request->search_query;
		if($request->search_type == 'vendor')
		{
			$search_vendor=Vendor::where('shop_name','like', '%' . $q . '%')->limit(5)->get();
			$response['vendor']=$search_vendor;
		}
		else{
			
			$search_product=Vendor_Product::where('product_name','like', '%' . $q . '%')->limit(5)->get();
			$search_vendor=Vendor::where('shop_name','like', '%' . $q . '%')->limit(5)->get();
			$response['product']=$search_product;
			$response['vendor']=$search_vendor;
		}
		
		$response['status']=true;
		
		return json_encode($response);
	}
		
	//get recent view shops
		public function recent_view_shops(Request $request)
		{
			$user_id=Auth::user()->id;
			
			$data=Vendor::whereIn('id',function($q) use($user_id){
				$q->from('vendor_shop_visit')->selectRaw('vendor_id')->where('user_id',$user_id)->orderBy('id', 'DESC');
			})->limit(10)->get();
			
		$response['status']=true;
		$response['data']=$data;
		return json_encode($response);
		}
	
    //get user profile 
    public function get_user_profile(Request $request)
    {
        $user_id=Auth::user()->id;
        $user=User::find($user_id);
        //return $user;
        if($user!=null)
        {
            $response['status']=true;
            $response['data']=$user;
        }
        else{
            $response['status']=false;
            $response['msg']="Invalid token";
        }

        return json_encode($response);
    }


    //function for update profile of user
    public function update_profile(Request $request)
    {
        $validator = Validator::make($request->all(), [ 
            'name' => 'required', 
        ]);

		if ($validator->fails())
    	{
        	return response(['errors'=>$validator->errors()->all()], 422);
    	}
        
        $user_id=Auth::user()->id;
		
        $user=User::find($user_id);
		
		if(isset($request->update_type))
		{
			$str_result = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890abcdefghijklmnopqrstuvwxyz'; 
			
			$name=substr(str_replace(' ', '',$request->name), 0, 4); 
			$rand= substr(str_shuffle($str_result), 0, 6); 
			$user->share_code =strtoupper($name.$rand);
		}
        $user->name=$request->name;
        $user->email=$request->email;
        $user->dob=$request->dob;
        $user->gender=$request->gender;
		
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
    public function update_profile_picture(Request $request)
    {
        $validator = Validator::make($request->all(), [ 
            'update_profile_picture'=> 'required|image|mimes:jpeg,png,jpg,gif,webp,svg,tmp'
        ]);

		if ($validator->fails())
    	{
        	return response(['errors'=>$validator->errors()->all()], 422);
    	}
        
	
        //condition to check iF file exits or not
        if($request->hasFile('update_profile_picture'))
        {
			
            $pic=$request->file('update_profile_picture');

            $path="profile_pic";

            //create unique name of file uploaded.
            $name=time().'_'.$pic->getClientOriginalName();
            if($pic->move($path,$name))
            {
                $user_id=Auth::user()->id;
                $user=user::find($user_id);
                $user->profile_pic=$path."/".$name;
                
                if($user->save())
                {
                    $response['status']=true;
                    $response['profile_pic']=$path."/".$name;
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


    //get vendor data 
    public function get_category_vendors(Request $request)
    {
        $validator = Validator::make($request->all(), [ 
           'category_id'=>'required',
            'latitude'=>'required',
            'longitude'=>'required',
			'sort_by'=>'required'
        ]);

		if ($validator->fails())
    	{
        	return response(['errors'=>$validator->errors()->all()], 422);
    	}

        $cat= $request->category_id;

        $haversine = "(6371 * acos(cos(radians(" . $request->latitude . ")) 
        * cos(radians(`shop_latitude`)) 
        * cos(radians(`shop_longitude`) 
        - radians(" . $request->longitude . ")) 
        + sin(radians(" . $request->latitude . ")) 
        * sin(radians(`shop_latitude`))))";
		
		if($request->sort_by == 'nearby')
		{
			$data=Vendor::with('offers')->with('today_timing')
		->select("vendors.id",'vendors.shop_name','vendors.profile_pic','vendors.address','vendors.current_rating')
		->selectRaw("{$haversine} AS distance")->whereIn('vendors.id', function ($query) use ($cat){
        $query->from('vendor_main_categories')->select('vendor_id')->where('category_id',$cat);
        })
		->having('distance','<','25')
		->orderBy('distance')
		->paginate(10);
		}
		if($request->sort_by == 'high_to_low'){
		
		$data=Vendor::with('offers')->with('today_timing')
		->select("vendors.id",'vendors.shop_name','vendors.profile_pic','vendors.address','vendors.current_rating')
		->selectRaw("{$haversine} AS distance")->addSelect(['discount' => Vendor_Offer::select('offer')->whereColumn('vendor_id', 'vendors.id')->orderBy('offer','ASC')->limit('1')])->whereIn('vendors.id', function ($query) use ($cat){
        $query->from('vendor_main_categories')->select('vendor_id')->where('category_id',$cat);
        })
		->having('distance','<','25')
		->orderBy('discount','ASC')
		->paginate(10);
		
			
		}else if($request->sort_by == 'low_to_high'){
				$data=Vendor::with('offers')->with('today_timing')
		->select("vendors.id",'vendors.shop_name','vendors.profile_pic','vendors.address','vendors.current_rating')
		->selectRaw("{$haversine} AS distance")->addSelect(['discount' => Vendor_Offer::select('offer')->whereColumn('vendor_id', 'vendors.id')->orderBy('offer','ASC')->limit('1')])->whereIn('vendors.id', function ($query) use ($cat){
        $query->from('vendor_main_categories')->select('vendor_id')->where('category_id',$cat);
        })
		->having('distance','<','25')
		->orderBy('discount','DESC')
		->paginate(10);
		
		}
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
	
	
	//get user profile 
      public function fetch_user_profile_different(Request $request)
      {
		  
		  $validator = Validator::make($request->all(), [ 
           'user_id'=>'required',
        ]);

		if ($validator->fails())
    	{
        	return response(['errors'=>$validator->errors()->all()], 422);
    	}
		
          $user_id=$request->user_id;
          $user=User::addSelect(['vendor_follow' =>Vendors_Subsciber::selectRaw('count(*)')->where('user_id',$user_id)])
		  ->addSelect(['feeds_count' =>Feed::selectRaw('count(*)')->where('user_type', 'user')->where('vendor_id',$user_id)])->where('id',$user_id)->get();
  
          if($user!=null)
          {
              $response['status']=true;
              $response['data']=$user;
          }
          else{
              $response['status']=false;
              $response['msg']="Invalid token";
          }
  
          return json_encode($response);
      }
	  

    public function follow_vendor_user(Request $request)
	{
		 $validator = Validator::make($request->all(), [ 
            'vendor_id' => 'required',
			 'type' => 'required',
        ]);

		if ($validator->fails())
    	{
        	return response(['errors'=>$validator->errors()->all()], 422);
    	}
		
		
		 if($request->type=='yes')
        {
            $feed=new Vendors_Subsciber;
            $feed->user_id=Auth::user()->id;
            $feed->vendor_id=$request->vendor_id;

            if($feed->save())
        {
            $response['status']=true;
            $response['msg']="Saved";
			
			
			//notification details 
			$heading_user= Auth::user()->name." Started following you.";
			
			$receiver_id=$request->vendor_id;
			
			$post_url=env('NOTIFICATION_VENDOR_URL')."/follower/". Auth::user()->id;
			$user_id=Auth::user()->id;
			$user_type="vendor";
			
			
			ProcessPush::dispatch($heading_user,$post_url,$receiver_id,$user_type,'');
			
        }
        else{
            $response['status']=false;
            $response['msg']="Not Updated";
        }
        }
        else if($request->type=='no'){

            $res=Vendors_Subsciber::where('vendor_id',$request->vendor_id)->where('user_id',Auth::user()->id)->delete();

            if($res)
            {
                $response['status']=true;
                $response['msg']="UnSaved";
            }
            else{
                $response['status']=false;
                $response['msg']="Not Updated";
            }
        }
        else{
            $response['status']=false;
                $response['msg']="Invalid type";
        }
		 echo json_encode($response,JSON_UNESCAPED_SLASHES);

	}
    
    public function fetch_home_sliders()
    {
        $data=Slider::where('status','Active')->get();
    
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
