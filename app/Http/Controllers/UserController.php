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
use App\Models\user_refer_log;
use App\Models\point_level;
use App\Models\user_fev_vendors;
use App\Models\user_payment_method;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use App\Models\Notification;
use App\Jobs\Processmail;
use App\Jobs\ProcessPush;
use App\Http\Controllers\UserTransactionController;
use App\Helpers\AppHelper;
use App\Models\user_txn_log;
use App\Models\user_follower;

use App\Models\UserOrders;

class UserController extends Controller
{
//fetch top categories

public function fetch_top_category()
{
  $categories = Category::where('parent_id',0)->where('status','Active')
  ->whereIn('categories.id',function($query){
    $query->select('category_id')->from('vendor_main_categories');
  })
  ->get();
  return response()->json(['status'=>true,'data'=>$categories]);
}	
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
		//return $request;
		//Cashback Initiated
		
		
		$validator = Validator::make($request->all(), [ 
            'rating' => 'required', 
            'vendor_id' => 'required',
			'review' => 'nullable',
        ]);

		if ($validator->fails())
    	{
        	return response(['errors'=>$validator->errors()->all()], 422);
    	}

		if(isset($request->review_type))
		{
			$review_type=$request->review_type;
		}
		else
		{
			$review_type="search";
		}
		$user_id = Auth::user()->id;
		$user_name = Auth::user()->name;
		$vr=new vendor_rating;
		//return Auth::user()->id;
		$vr->vendor_id=$request->vendor_id;
		$vr->user_id =	$user_id;
		$vr->vendor_rating =	$request->rating;
		$vr->vendor_review =	$request->review;
		$vr->review_status = 'success';
		$vr->review_count = 'yes';
		
		//It is checked for it comes from scan or any other mode
		$vr->review_from = $review_type;
		$res = vendor_rating::where('vendor_id',$request->vendor_id)->where('user_id',$user_id)->update(['review_count'=>'no']);
		try{
			$permission=new UserTransactionController();
			if($vr->save()){
				
				$res = Vendor::where('id',$request->vendor_id)
				->update(['current_rating'=> vendor_rating::where('vendor_id',$request->vendor_id)->where('review_count','yes')->avg('vendor_rating')]);
				
			}
			//Point credit to User
			$coin = point_level::get();
			$check_user_rating_count = vendor_rating::where('user_id',$user_id)->count();
			if($check_user_rating_count == 1){
				//return "Hello";
				 // $permission->credit_coin($request->vendor_id,'12345',$coin[0]->review_point,'Success','UPI');
				$refer_amount = DB::table('refer_earn_setups')->get();
				$today_earning = user_txn_log::where('user_id',Auth::user()->id)->where('txn_status','success')->whereDate('created_at',date('Y-m-d'))->sum('txn_amount');
				if($today_earning <= $coin[0]->max_point_per_day){
					$heading_user= $refer_amount[0]->earner." MP coins has been initiated to your wallet for the feed review.";
					//Point credit to User
					$permission->credit_coin($user_id,$heading_user,$refer_amount[0]->earner,'success','credit');
					
					
					$heading_vendor= $user_name." gives you a rating";
					$post_url=" ";
					//Notification to User
					ProcessPush::dispatch($heading_user,$post_url,$user_id,'user','');
					//Notification to vendor
					ProcessPush::dispatch($heading_vendor,$post_url,$request->vendor_id,'vendor','');
				}
				
				if($request->review_type == 'scan'){
					$given_coin = $refer_amount[0]->referrer;
				}else{
					$given_coin = $refer_amount[0]->referrer;
				}
				//return $given_coin;
				$refer_by = user_refer_log::where('user_id',$user_id)->orderBy('id','ASC')->get();
				//return $refer_by;
				if(count($refer_by) >0 ){
					$heading_user= $given_coin." MP Coins has been initiated for review done by ".$user_name;
					//Point credit to User
					$permission->credit_coin($refer_by[0]->refer_id,$heading_user,$given_coin,'success','credit');
					
					$post_url=" ";
					//Notification to User
					ProcessPush::dispatch($heading_user,$post_url,$refer_by[0]->refer_id,'user','');
				}
			}else{
				$today_earning = user_txn_log::where('user_id',Auth::user()->id)->where('txn_status','success')->whereDate('created_at',date('Y-m-d'))->sum('txn_amount');
				//return $today_earning;
				if($today_earning <= $coin[0]->max_point_per_day){
					$heading_user= $coin[0]->review_point." MP coins has been initiated to your wallet for the feed review.";
					//Point credit to User
					$permission->credit_coin($user_id,$heading_user,$coin[0]->review_point,'success','credit');
					
					
					$heading_vendor= $user_name." gives you a rating";
					$post_url=" ";
					//Notification to User
					ProcessPush::dispatch($heading_user,$post_url,$user_id,'user','');
					//Notification to vendor
					ProcessPush::dispatch($heading_vendor,$post_url,$request->vendor_id,'vendor','');
				}
			}
			
		  
			
			$response['status']=true;
			$response['msg']="successfully submited";
	
		}
		catch(Exception $e)
		{
			return "Ddd";
		}
		
		return json_encode($response);
	}
	
	public function fetch_payment_methods ()
	{
		$user_id=Auth::user()->id;

		$data=user_payment_method::where('user_id',$user_id)->get();

		if(count($data)>0)
		{
			$response['status']=true;
			$response['data']=$data;
		}
		else
		{
			$response['status']=true;
			$response['msg']="No Methods Found.";
		}

		return json_encode($response,JSON_UNESCAPED_SLASHES);
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
			$response['status']=false;
		$response['msg']="No data found.";
		}
		
		return json_encode($response,JSON_UNESCAPED_SLASHES);
	}
	
	
	public function get_vendor_reviews(Request $request)
	{
		//return $request;
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

		
		$rating_per=vendor_rating::selectRaw('count(vendor_rating)/'.$total_rating.' as percentage,vendor_rating')
		->where('vendor_id',$vendor_id)
		->groupBy('vendor_rating')
		->orderBy('vendor_rating')
		->get();
		//return $rating_per;
		//$vr=vendor_rating::selectRaw('count(*) as cc')->addSelect(['rate1' =>vendor_rating::selectRaw('count(*)/cc')->whereColumn('vendor_id', 'vendor_ratings.vendor_id')->where('vendor_rating',5)])->where('vendor_ratings.vendor_id',$vendor_id)->get();
		
		if(count($vr)>0)
		{
			$response['status']=true;
			$response['data']=$vr;
			$response['total_rating']=$total_rating;
			$response['data'][0]['rating_percentage']=$rating_per;
		}
		else
		{
			$response['status']=false;
		$response['msg']="No data found.";
		}
		//return $response;
		return json_encode($response,JSON_UNESCAPED_SLASHES);
	}
	
	
	public function get_earn_data()
	{
		$data=refer_earn_setup::all();
		$ref = DB::table('refer_earn_setups')->get();
		$data2=point_level::all();
		$data['wallet']=Auth::user()->wallet;
		$data['referrer'] = $ref[0]->referrer;
		$data['earner'] = $ref[0]->earner;
		$data['upi']=Auth::user()->upi_id;
		$data['share_code']=Auth::user()->share_code;
		$response['status']=true;
		$response['data']=$data;
		
		$response['data2']=$data2;
		
		//return $response;
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
		if(isset($request->cat_type))
		{
			$cat_type=$request->cat_type;
		}
		else
		{
			$cat_type="home";
		}
		
		if($request->category_id != 0)
		{
			 $category= $category=Category::where('parent_id',$request->category_id)->where('status','Active')->take(13)->get();
		}else{
			 $category=Category::where('status','Active')->where('parent_id',0)->take(13)->get();
		}
		
		$x=0;
		if($cat_type !="all" && count($category)>12)
		{
			$cat=[];
			for($x=0; $x<11; $x++)
			{
				$cat[$x]=$category[$x];
			}
			$cat[$x]['category_name']="More";
			$cat[$x]['category_icon']="1637577517.png";
			
			$response['data']=$cat;
		}
		else
		{
			$response['data']=$category;
		}
       //return $category;
       
        

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
			$data=Vendor::where('status','Active')->whereIn('id',function($q) use($user_id){
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
			$search_vendor=Vendor::where('status','Active')->where('shop_name','like', '%' . $q . '%')->limit(5)->get();
			$response['vendor']=$search_vendor;
		}
		else if($request->search_type == 'payment')
		{
			$search_vendor=Vendor::where('status','Active')->where('shop_name','like', '%' . $q . '%')->where('payment_accept',1)->limit(5)->get();
			$response['vendor']=$search_vendor;
		}
		else{
			
			$search_product=Vendor_Product::where('product_name','like', '%' . $q . '%')->limit(5)->get();
			$search_vendor=Vendor::where('status','Active')->where('shop_name','like', '%' . $q . '%')->limit(5)->get();
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
			
			$data=Vendor::where('status','Active')->whereIn('id',function($q) use($user_id){
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
        $user=User::addSelect(['vendor_follow' =>Vendors_Subsciber::selectRaw('count(*)')->where('user_id',$user_id)])
		  ->addSelect(['followers' => user_follower::selectRaw('count(*)')->where('following_id',$user_id)])
		  ->addSelect(['cashback' =>UserOrders::selectRaw('sum(order_discount)')->where('order_status', 'completed')->where('user_id',$user_id)])->
		where('id',$user_id)->get();

		$user=User::where('id',$user_id)->get();
        //return $user;
        if(count($user)>0)
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
		//return $user_id;
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

            $path="profile_pic/";

             $globalclass=new GlobalController();
						 //Remove Previous Image
						$globalclass->removeprevious();
			
		      	$res=$globalclass->upload_img($pic,$path);
			
            if($res['status'])
            {
				$name=$res['file_name'];
                $user_id=Auth::user()->id;
                $user=user::find($user_id);
                $user->profile_pic=$name;
                
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
			$data=Vendor::with('offer')->with('today_timing')->with('favourite_my')
			->select("vendors.is_prime","vendors.status","vendors.id",'vendors.shop_name','vendors.profile_pic','vendors.address','vendors.current_rating','vendors.flat_deal_all_time')
			->where('vendors.status','Active')
			->selectRaw("{$haversine} AS distance")->whereIn('vendors.id', function ($query) use ($cat){
					$query->from('vendor_main_categories')->select('vendor_id')->where('category_id',$cat);
					})
			->having('distance','<','25')
			->orderBy('distance')->orderBy('flat_deal_all_time','DESC')
			->paginate(10);
		}
		if($request->sort_by == 'high_to_low'){
				$data=Vendor::with('offer')->with('today_timing')->with('favourite_my')->where('status','Active')
				->select("vendors.is_prime","vendors.status","vendors.id",'vendors.shop_name','vendors.profile_pic','vendors.address','vendors.current_rating','vendors.flat_deal_all_time')
				->where('vendors.status','Active')
				->selectRaw("{$haversine} AS distance")->addSelect(['discount' => Vendor_Offer::select('offer')->whereColumn('vendor_id', 'vendors.id')->orderBy('offer','ASC')->limit('1')])->whereIn('vendors.id', function ($query) use ($cat){
						$query->from('vendor_main_categories')->select('vendor_id')->where('category_id',$cat);
						})
				->having('distance','<','25')
				->orderBy('flat_deal_all_time','DESC')
				->paginate(10);
		}else if($request->sort_by == 'low_to_high'){
				$data=Vendor::with('offer')->with('today_timing')->with('favourite_my')
				->select("vendors.is_prime","vendors.status","vendors.id",'vendors.shop_name','vendors.profile_pic','vendors.address','vendors.current_rating','vendors.flat_deal_all_time')
				->where('vendors.status','Active')
				->selectRaw("{$haversine} AS distance")->addSelect(['discount' => Vendor_Offer::select('offer')->whereColumn('vendor_id', 'vendors.id')->orderBy('offer','ASC')->limit('1')])->whereIn('vendors.id', function ($query) use ($cat){
						$query->from('vendor_main_categories')->select('vendor_id')->where('category_id',$cat);
						})
				->having('distance','<','25')
				->orderBy('flat_deal_all_time','ASC')
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

		  $current_user=Auth::user()->id;
          $user=User::addSelect(['vendor_follow' =>Vendors_Subsciber::selectRaw('count(*)')->where('user_id',$user_id)])
		  ->addSelect(['is_following' => user_follower::selectRaw('count(*)')->where('follower_id',$current_user)->where('following_id',$user_id)])
		  -> addSelect(['followers' => user_follower::selectRaw('count(*)')->where('following_id',$user_id)])
		  ->addSelect(['feeds_count' =>Feed::selectRaw('count(*)')->where('user_type', 'user')->where('vendor_id',$user_id)])->where('id',$user_id)->get();
  
          if(count($user)>0)
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
			Vendors_Subsciber::where('vendor_id',$request->vendor_id)->where('user_id',Auth::user()->id)->delete();
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
    

	//function for make user followers 
	public function follow_user(Request $request)
	{
		$validator = Validator::make($request->all(), [ 
		   'user_id' => 'required',
			'type' => 'required',
	   ]);

	  // return Auth::user()->id;
	   if ($validator->fails())
	   {
		   return response(['errors'=>$validator->errors()->all()], 422);
	   }
	   
	   
		if($request->type=='yes')
	   {
			user_follower::where('following_id',$request->user_id)->where('follower_id',Auth::user()->id)->delete();
		   $feed=new user_follower;
		   $feed->follower_id=Auth::user()->id;
		   $feed->following_id=$request->user_id;

		   if($feed->save())
	   {
		   $response['status']=true;
		   $response['msg']="Saved";
		   
		   
		   //notification details 
		   $heading_user= Auth::user()->name." Started following you.";
		   
		   $receiver_id=$request->user_id;
		   
		   $post_url=env('NOTIFICATION_USER_URL')."/follower/". Auth::user()->id;
		   $user_id=Auth::user()->id;
		   $user_type="user";
		   
		   
		   ProcessPush::dispatch($heading_user,$post_url,$receiver_id,$user_type,'');
		   
	   }
	   else{
		   $response['status']=false;
		   $response['msg']="Not Updated";
	   }
	   }
	   else if($request->type=='no'){

		   $res=user_follower::where('following_id',$request->user_id)->where('follower_id',Auth::user()->id)->delete();

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
