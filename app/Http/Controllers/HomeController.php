<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\home_tab_controller;
use App\Models\home_tab_content;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
class HomeController extends Controller
{
    
	 public function fetch_home_data(Request $request){
		$user = Auth::user()->id;
		$udata = User::find($user);
		$haversine = "(6371 * acos(cos(radians(" . $udata->location_lati . ")) 
        * cos(radians(`shop_latitude`)) 
        * cos(radians(`shop_longitude`) 
        - radians(" . $udata->location_long . ")) 
        + sin(radians(" . $udata->location_lati . ")) 
        * sin(radians(`shop_latitude`))))";
		$nearby_vendor = Vendor::select(['name','email','contact','description'])
		->selectRaw("{$haversine} AS distance")
		->get();
		//return $uda;
		
        //return $haversine;
        $home_data=home_tab_controller::with('fetch_content')->where('status','active')->orderBy('sort_by','ASC')
		
		->get();
        if($home_data)
        {
            $response['status']=true;
            $response['data']=$home_data;
			$response['nearby_vendor']=$nearby_vendor;
        }
        else{
            $response['status']=false;
            $response['msg']="not found";
        }
        return $response;
        return json_encode($response,JSON_UNESCAPED_SLASHES);
	}
}
