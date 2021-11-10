<?php

namespace App\Http\Controllers;
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

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
class UserController extends Controller
{
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
            'update_profile_picture'=> 'required|image|mimes:jpeg,png,jpg,gif,webp,svg|max:2048'
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

            //create unique name of file uploaded.
            $name=time().'_'.$pic->getClientOriginalExtention();
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


    public function get_category_vendors(Request $request)
    {
        $validator = Validator::make($request->all(), [ 
            'page_id' => 'required', 
            'category_id'=>'required',
            'latitude'=>'required',
            'longitude'=>'required'
        ]);

		if ($validator->fails())
    	{
        	return response(['errors'=>$validator->errors()->all()], 422);
    	}

        $data=Vendor::select("id", "( 3959 * acos( cos( radians(".$request->latitude.") ) * cos ( radians( shop_latitude ) ) * cos ( radians( shop_longitude ) - radians (".$request->longitude.") ) + sin ( radians(".$request->latitude.") ) * sin ( radians( shop_latitude ) ) ) ) as distance")->where('category_id',$request->category_id)->having('distance', '<', 25)->orderBy('distance')->paginate($request->page_id);
        
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

        //fetch store details of vendor
        $store_data=Vendor::find($request->vendor_id);
        
        // echo $store_data;
        // exit;
        if($store_data!=null)
        {
            $response['status']=true;
            $response['data']=$store_data;

            $response['covers']=Vendor_cover::where('vendor_id',$request->vendor_id)->get();
            
            $response['categories']=Vendor_category::where('vendor_id',$request->vendor_id)->get();

            $response['products']=Vendor_Product::where('vendor_id',$request->vendor_id)->get();

            $response['data']['followers']=Vendors_Subsciber::where('vendor_id',$request->vendor_id)->count();
        }
        else{
            $response['status']=false;
            $response['msg']="Invalid Vendor Id, Try Again.";
        }

        echo json_encode($response,JSON_UNESCAPED_SLASHES); 
    }

    public function get_vendor_product(Request $request)
    {
        $validator = Validator::make($request->all(), [ 
            'vendor_category_id' => 'required',
        ]);

		if ($validator->fails())
    	{
        	return response(['errors'=>$validator->errors()->all()], 422);
    	}

         //fetch store details of vendor
         $store_data=Vendor_Product::where('vendor_category_id',$request->vendor_category_id)->get();
        
         // echo $store_data;
         // exit;
         if($store_data!=null)
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

}
