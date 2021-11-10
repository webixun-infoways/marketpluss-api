<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Vendor;
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
 
}
