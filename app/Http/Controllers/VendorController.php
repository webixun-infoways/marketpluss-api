<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Vendor;
use App\Models\vendor_main_categories;
use App\Models\Vendor_category;
use App\Models\Vendor_Offer;
use App\Models\Vendor_Offer_Product;
use App\Models\vendor_shop_visit;
use App\Models\Vendors_Subsciber;
use App\Models\Vendor_cover;
use App\Models\Vendor_Product;

use App\Models\Feed_Save;
use App\Models\Feed;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
class VendorController extends Controller
{
    public function get_vendor_data(Request $request)
    {
        $vendor_id=Auth::user()->id;

        $response['shop_visit']=vendor_shop_visit::where('vendor_id',$vendor_id)->where('user_activity','shop_visit')->count();
        $response['contact']=vendor_shop_visit::where('vendor_id',$vendor_id)->where('user_activity','contact')->count();

        $response['followers']=Vendors_Subsciber::where('vendor_id',$vendor_id)->count();
        $response['feed_save']=Feed_Save::whereIn('feed_id', function($q) use($vendor_id){
            $q->from('feeds')->where('vendor_id',$vendor_id)->selectRaw('id');
        })->count();

        $response['feed_view']=Feed::where('vendor_id',$vendor_id)->sum('feed_view');

        $res['status']=true;
        $res['data']=$response;
        return json_encode($res);
    }

      //get user profile 
      public function get_vendor_profile(Request $request)
      {
          $user_id=Auth::user()->id;
          $user=Vendor::find($user_id);
  
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
 
             $path="shop_pic/";
 
             //create unique name of file uploaded.
             $name=time().'_'.$pic->getClientOriginalName();
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
             $name=time().'_'.$pic->getClientOriginalName();
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
 
             //create unique name of file uploaded.
             $name=time().'_'.$pic->getClientOriginalName();
             if($pic->move($path,$name))
             {
                $path=$path."/".$name;

                $v_product=new Vendor_Product;
                $v_product->product_name=$request->product_name;
                $v_product->market_price=$request->market_price;
                $v_product->our_price=$request->price;
                $v_product->description=$request->description;
                $v_product->status='active';
                $v_product->vendor_id=$vendor_id;
                $v_product->vendor_category_id=$request->vendor_category_id;
                $v_product->product_img=$name;
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
             $name=time().'_'.$pic->getClientOriginalName();
             if($pic->move($path,$name))
             {
                $path=$path."/".$name;

                $v_product= Vendor_Product::find($request->product_id);
                $v_product->product_name=$request->product_name;
                $v_product->market_price=$request->market_price;
                $v_product->our_price=$request->price;
                $v_product->description=$request->description;
                $v_product->status='active';
                $v_product->vendor_id=$vendor_id;
                $v_product->vendor_category_id=$request->vendor_category_id;
                $v_product->product_img=$name;
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
            // 'offer_type'=> 'required',
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
        // $offer->offer_type=$request->offer_type;
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
             $store_data=Vendor_Product::where('vendor_category_id',$request->vendor_category_id)->where('status','active')->get();
        }
        else if($request->vendor_category_id == 0 && $request->product_type == 'product')
        {    
            //fetch store details of vendor
            $store_data=Vendor_Product::where('type',$request->product_type)->where('status','active')->get();
        }
        else if($request->vendor_category_id != 0 && $request->product_type == 'package')
        {    
            //fetch store details of vendor
            $store_data=Vendor_Product::where('type',$request->product_type)->where('status','active')->get();
        }
        else
        {    
            //fetch store details of vendor
            $store_data=Vendor_Product::where('type',$request->product_type)->where('status','active')->get();
        }
        
        
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
		 //return $vendor_id;
        //return $request_category_id;
        if($request->vendor_category_id != 0 && $request->product_type == 'product')
        {
             //fetch store details of vendor
             $store_data=Vendor_Product::where('vendor_category_id',$request->vendor_category_id)->where('type',$request->product_type)->where('vendor_id',$vendor_id)->where('status','active')->get();
        }
        else if($request->vendor_category_id == 0 && $request->product_type == 'product')
        {    
            //fetch store details of vendor
            $store_data=Vendor_Product::where('type',$request->product_type)->where('vendor_id',$vendor_id)->where('status','active')->get();
        }
        else if($request->vendor_category_id != 0 && $request->product_type == 'package')
        {    
            //fetch store details of vendor
            $store_data=Vendor_Product::where('type',$request->product_type)->where('vendor_id',$vendor_id)->where('status','active')->get();
        }
        else
        {    
            //fetch store details of vendor
            $store_data=Vendor_Product::where('type',$request->product_type->where('vendor_id',$vendor_id))->where('status','active')->get();
        }
        
        
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

        if($request->vendor_id != 0)
        {
           //fetch store details of vendor
            $store_data=Vendor_Product::where('vendor_id',$request->vendor_id)->whereIn('id',function($q) use($vendor_id){
           
            $q->from('vendor_offer_products')->selectRaw('product_id')->whereIn('offer_id', function($qe) use($vendor_id){
            
                $qe->from('vendor_offers')->selectRaw('id')->where('vendor_id',$vendor_id);
            });
            })->get();
        }
        else{

            if($request->category_id != 0)
            {
                $cate_id=$request->category_id;
                $store_data=Vendor::join('vendor_products','vendor_products.vendor_id','vendors.id')->selectRaw("{$haversine} AS distance")->whereIn('vendor_products.id',function($q) use($vendor_id){
                    $q->from('vendor_offer_products')->selectRaw('product_id')->whereIn('offer_id', function($qe) use($vendor_id){
                        $qe->from('vendor_offers')->selectRaw('id');
                    });
                    })->whereIn('vendors.id',function($q) use($cate_id){
                        $q->from('vendor_main_categories')->selectRaw('vendor_id')->where('category_id',$cate_id);
                    })->having('distance','<','25')->orderBy('distance');

            }
            else{
                $store_data=Vendor_Product::whereIn('id',function($q) use($vendor_id){
           
                    $q->from('vendor_offer_products')->selectRaw('product_id')->whereIn('offer_id', function($qe) use($vendor_id){
                    
                        $qe->from('vendor_offers')->selectRaw('id');
                    });
                    })->get();
            }
        }
        
        
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
    
	
	
	//fetch vendor_offers
	 public function get_vendor_offers_vendor(Request $request)
    {
		//return $request;
        $validator = Validator::make($request->all(), [ 
           
            'category_id' =>'required'
        ]);


        if ($validator->fails())
        {
            return response(['errors'=>$validator->errors()->all()], 422);
        }

        $vendor_id=Auth::user()->id;

       

            if($request->category_id != 0)
            {
                $cate_id=$request->category_id;
                $store_data=Vendor::join('vendor_products','vendor_products.vendor_id','vendors.id')->whereIn('vendor_products.id',function($q) use($vendor_id){
                    $q->from('vendor_offer_products')->selectRaw('product_id')->whereIn('offer_id', function($qe) use($vendor_id){
                        $qe->from('vendor_offers')->selectRaw('id');
                    });
                    })->whereIn('vendors.id',[$vendor_id]);
                

            }
            else{
                $store_data=Vendor_Product::whereIn('id',function($q) use($vendor_id){
           
                    $q->from('vendor_offer_products')->selectRaw('product_id')->whereIn('offer_id', function($qe) use($vendor_id){
                    
                        $qe->from('vendor_offers')->selectRaw('id');
                    });
                    })->get();
            }
        
        
        
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
        
        $shop_visit = new vendor_shop_visit;

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
    
    
}
