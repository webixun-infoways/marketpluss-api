<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\home_tab_controller;
use App\Models\home_tab_content;

class HomeController extends Controller
{
    
	 public function fetch_home_data(Request $request){
		

        $home_data=home_tab_controller::with('fetch_content')->where('status','active')->orderBy('sort_by','ASC')->get();
        if($home_data)
        {
            $response['status']=true;
            $response['data']=$home_data;
        }
        else{
            $response['status']=false;
            $response['msg']="not found";
        }

        return json_encode($response,JSON_UNESCAPED_SLASHES);
	}
}
