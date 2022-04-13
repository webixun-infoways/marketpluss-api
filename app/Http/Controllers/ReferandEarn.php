<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Mail;
use DB;

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
use App\Models\user_refer_log;
use App\Models\refer_earn_setup;
use App\Models\point_level;
use App\Models\user_fev_vendors;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use App\Models\Notification;
use App\Jobs\Processmail;
use App\Jobs\ProcessPush;
use Redirect;
use App\Helpers\AppHelper;
class ReferandEarn extends Controller
{
    //fetch front category for user & vendor
    public function genrateRequest(Request $request)
    {
		$getip = AppHelper::get_ip();
		$getbrowser = AppHelper::get_browsers();
		$getdevice = AppHelper::get_device();
		$getos = AppHelper::get_os();
		
		$refer_id=$request->refer_id;
		
		//if($getdevice == 'Mobile' && $getos  == 'Android' || $getos  == 'iPhone')
		//{
			$data=User::where("share_code",$refer_id)->get("id");
			

			if(count($data)>0)
			{
				$refer=new user_refer_log;
				$refer->refer_id =$data[0]->id;
				$refer->user_ip_address=$getip;
				$refer->user_device=$getdevice;
				$refer->user_os=$getos;
				$refer->refer_status ="pending";
				$refer->refer_amount=0;
				$refer->save();
			}
			else
			{
				return Redirect::to("https://play.google.com/store/apps/details?id=com.marketpluss_user");
			}
			
	//	}
		//else
		//{
	//		echo "soory";
		//}
		
		
		
   // return "<center>$getip <br> $getdevice <br> $getbrowser <br> $getos</center>";
		//echo $request->id;
		
	$response['user'] = $getdevice;
	$response['hh'] = $getos;
	
	if($getos == 'ios')
	{
		return Redirect::to("https://play.google.com/store/apps/details?id=com.marketpluss_user");
	}
	else
	{
		return Redirect::to("https://play.google.com/store/apps/details?id=com.marketpluss_user");
	}
	
		//echo json_encode($response);
    }
}
