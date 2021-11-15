<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Vendor;
use App\Models\vendor_main_categories;
use App\Models\Vendor_category;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
class VendorController extends Controller
{
     //function for update profile of user
     public function update_profile_vendor(Request $request)
     {
         $validator = Validator::make($request->all(), [ 
             'name' => 'required', 
             'email' => 'email',
             'shop_name'=>'required',
         ]);
 
         if ($validator->fails())
         {
             return response(['errors'=>$validator->errors()->all()], 422);
         }
         
        $vendor_id=Auth::user()->id;
         $user=vendor::find($vendor_id);
         $user->name=$request->name;
         $user->email=$request->email;
         $user->shop_name=$request->shop_name;
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
 
             $path="shop_pic/";
 
             //create unique name of file uploaded.
             $name=time().'_'.$pic->getClientOriginalExtention();
             if($pic->move($path,$name))
             {
                $vendor_id=Auth::user()->id;
                $user=vendor::find($vendor_id);
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
            $response['status']=false;
            $response['msg']="Invalid Request!";
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
      
    }

    //update vendor servicess

    public function vendor_update_product(Request $request)
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
      
    }

    //add new packeges

    public function vendor_add_package(Request $request)
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
      
    }


    //update packages
    public function vendor_update_package(Request $request)
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
      
    }

}
