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
use App\Models\Feed_Comment;
use App\Models\Feed;
use App\Models\Slider;
use App\Models\Vendor_cover;
use App\Models\Category;
use App\Models\user_product_saves;
use App\Models\user_fev_vendors;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;

use App\Jobs\Processmail;

class UserController extends Controller
{
	//fetch front category for user & vendor
    public function send_mail(Request $request)
    {
		Processmail::dispatch();
      echo "done";
    }
	
	
	
    //fetch front category for user & vendor
    public function get_all_category(Request $request)
    {
        $category=Category::all();
        $response['data']=$category;

        return json_encode($response);
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
        ]);

		if ($validator->fails())
    	{
        	return response(['errors'=>$validator->errors()->all()], 422);
    	}
		
		$q=$request->search_query;
		$search_product=Vendor_Product::where('product_name','like', '%' . $q . '%')->get();
		
		$search_vendor=Vendor::where('shop_name','like', '%' . $q . '%')->get();
		
		$response['status']=true;
		$response['product']=$search_product;
		$response['vendor']=$search_vendor;
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
            'email' => 'email'
        ]);

		if ($validator->fails())
    	{
        	return response(['errors'=>$validator->errors()->all()], 422);
    	}
        
        $user_id=Auth::user()->id;

        $user=User::find($user_id);
        $user->name=$request->name;
        $user->email=$request->email;
        $user->dob=$request->email;
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
            'longitude'=>'required'
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

        $data=DB::table('vendors')->join('vendor_offers','vendors.id','=','vendor_offers.vendor_id')
		->select("vendors.id",'vendors.shop_name','vendors.profile_pic','vendor_offers.offer')
		->selectRaw("{$haversine} AS distance")->whereIn('vendors.id', function ($query) use ($cat){
        $query->from('vendor_main_categories')->select('vendor_id')->where('category_id',$cat);
        })
		//->having('distance','<','25')
		->orderBy('distance')
		->paginate(10);
        
		
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
          $user=User::find($user_id);
  
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
	
	public function sort_by(Request $request)
	{
		$validator = Validator::make($request->all(), [ 
            'sort_by'=>'required',
			'shop_latitude'=>'required',
			'shop_longitude'=>'required'
        ]);

		if ($validator->fails())
    	{
        	return response(['errors'=>$validator->errors()->all()], 422);
    	}
		

		$haversine = "(6371 * acos(cos(radians(" . $request->shop_latitude . ")) 
		* cos(radians(`shop_latitude`)) 
		* cos(radians(`shop_longitude`) 
		- radians(" . $request->shop_longitude . ")) 
		+ sin(radians(" . $request->shop_latitude . ")) 
		* sin(radians(`shop_latitude`))))";
		if($request->sort_by == 'high_to_low'){
			$data=DB::table('vendors')->join('vendor_offers','vendors.id','=','vendor_offers.vendor_id')->select('vendors.id','vendors.shop_name','vendors.profile_pic','vendor_offers.offer')->selectRaw("{$haversine} AS distance")->whereIn('vendors.id', function ($query){
			$query->from('vendor_main_categories')->select('vendor_id');
			})->orderBy('vendor_offers.offer','DESC')->paginate(10);
			
		}else if($request->sort_by == 'low_to_high'){
			$data=DB::table('vendors')->join('vendor_offers','vendors.id','=','vendor_offers.vendor_id')->select('vendors.id','vendors.shop_name','vendors.profile_pic','vendor_offers.offer')->selectRaw("{$haversine} AS distance")->whereIn('vendors.id', function ($query){
			$query->from('vendor_main_categories')->select('vendor_id');
			})->orderBy('vendor_offers.offer','ASC')->paginate(10);
		}
		

        if(sizeof($data)>0)
        {
            $response['status']=true;
                $response['data']=$data;
            }
            else
            {
                $response['status']=false;
                $response['msg']="No Data Found";
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
        $data=Slider::all();
    
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
