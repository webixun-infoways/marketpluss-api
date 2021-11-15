<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Vendor;
use App\Models\vendor_main_categories;
use App\Models\Vendor_category;
use App\Models\Vendor_Offer;
use App\Models\Vendor_Offer_Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
class VendorController extends Controller
{
    public function get_vendor_data(Request $request)
    {
        $shop_visit=
    }
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
 
             $path="shop_pic/";
 
             //create unique name of file uploaded.
             $name=time().'_'.$pic->getClientOriginalExtention();
             if($pic->move($path,$name))
             {
                $vendor_id=Auth::user()->id;
                $user= new vendor_cover;
                $user->image=$path."/".$name;
                $user->vendor_id=$vendor_id;
                $user->status='active';

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
 
             //create unique name of file uploaded.
             $name=time().'_'.$pic->getClientOriginalExtention();
             if($pic->move($path,$name))
             {
                $path=$path."/".$name;

                $v_product=new Vendor_Product;
                $v_product->product_name=$request->product_name;
                $v_product->market_price=$request->product_market_price;
                $v_product->our_price=$request->price;
                $v_product->description=$request->description;
                $v_product->status='active';
                $v_product->vendor_id=$vendor_id;
                $v_product->vendor_category_id=$request->vendor_category_id;
                $v_product->product_img=$request->$path;
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
 
             //create unique name of file uploaded.
             $name=time().'_'.$pic->getClientOriginalExtention();
             if($pic->move($path,$name))
             {
                $path=$path."/".$name;

                $v_product= Vendor_Product::find($request->product_id);
                $v_product->product_name=$request->product_name;
                $v_product->market_price=$request->product_market_price;
                $v_product->our_price=$request->price;
                $v_product->description=$request->description;
                $v_product->status='active';
                $v_product->vendor_id=$vendor_id;
                $v_product->vendor_category_id=$request->vendor_category_id;
                $v_product->product_img=$request->$path;
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
        $validator = Validator::make($request->all(), [ 
            'offer_name'=> 'required',
            'offer_type'=> 'required',
            'offer'=> 'required',
            'start_date'=> 'required',
            'end_date'=>'required',
            'vendor_id'=>'required'
        ]);

        if ($validator->fails())
        {
            return response(['errors'=>$validator->errors()->all()], 422);
        }
        
        $offer=new Vendor_Offer;
        $vendor_id=Auth::user()->id;
        $offer->offer_name=$request->offer_name;
        $offer->offer_type=$request->offer_type;
        $offer->offer=$request->offer;
        $offer->start_from=$request->start_date;
        $offer->start_to =$request->end_date;
        $offer->status =$request->offer_name;
        $offer->vendor_id= $vendor_id;
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
        $validator = Validator::make($request->all(), [ 
            'offer_name'=> 'required',
            'offer_type'=> 'required',
            'offer'=> 'required',
            'start_date'=> 'required',
            'end_date'=>'required',
            'vendor_id'=>'required',
            'offer_id'=>'required',
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
        $offer->status =$request->offer_name;
        if($offer->save())
        {
            if(Vendor_Offer_Product::where('offer_id',$request->offer_id)->delete())
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
            }
            else
            {
                $response['status']=false;
                $response['msg']="Offer could not be Added!";
            }
        }
        }
        else{
                $response['status']=false;
                $response['msg']="Offer could not be Added!";
            
        }


        return json_encode($response);
    }


    public function get_category_vendor(Request $request)
    {
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
    // //add new packeges

    // public function vendor_add_package(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [ 
    //         'name'=> 'required',
    //         'vendor_category_id'=> 'required',
    //         'market_price'=> 'required',
    //         'price'=> 'required',
    //         'product_img'=> 'required',
    //     ]);

    //     if ($validator->fails())
    //     {
    //         return response(['errors'=>$validator->errors()->all()], 422);
    //     }
        
    //     $vendor_id=Auth::user()->id;

    //      //condition to check iF file exits or not
    //      if($request->hasFile('product_img'))
    //      {
    //          $pic=$request->file('product_img');
    //          $path="products/";
 
    //          //create unique name of file uploaded.
    //          $name=time().'_'.$pic->getClientOriginalExtention();
    //          if($pic->move($path,$name))
    //          {
    //             $path=$path."/".$name;

    //             $v_product=new Vendor_Package;
    //             $v_product->product_name=$request->name;
    //             $v_product->market_price=$request->market_price;
    //             $v_product->our_price=$request->price;
    //             $v_product->description=$request->description;
    //             $v_product->status='active';
    //             $v_product->vendor_id=$vendor_id;
    //             $v_product->vendor_category_id=$request->vendor_category_id;
    //             $v_product->product_img=$request->$path;
                
    //             if($v_product->save())
    //             {
    //                 $response['status']=true;
    //                 $response['msg']="Product Added!";
    //             }
    //             else
    //             {
    //                 $response['status']=false;
    //                 $response['msg']="Product could not be Added!";
    //             }
    //          }
    //          else{
    //              $response['status']=false;
    //              $response['msg']="img could not be updated!";
    //          }
      
    //      }
    //      else{
    //          $response['status']=false;
    //          $response['msg']="Invalid File";
    //      }
      
    // }


    // //update packages
    // public function vendor_update_package(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [ 
    //         'latitude'=> 'required',
    //         'longitude'=> 'required',
    //         'area'=> 'required',
    //         'city'=> 'required',
    //         'state'=> 'required',
    //         'address'=> 'required',
    //         'pincode'=> 'required',
    //     ]);

    //     if ($validator->fails())
    //     {
    //         return response(['errors'=>$validator->errors()->all()], 422);
    //     }
      
    // }

}
