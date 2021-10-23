<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Feed_like;
use Illuminate\Support\Facades\Auth;
class UserController extends Controller
{
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


    //function for feed likes
    public function feed_like(Request $request)
    {
        $validator = Validator::make($request->all(), [ 
            'feed_id' => 'required', 
            'type'=>'required'
        ]);

		if ($validator->fails())
    	{
        	return response(['errors'=>$validator->errors()->all()], 422);
    	}

        if($request->type=='yes')
        {
            $feed=new Feed_like;
            $feed->user_id=Auth::user()->id;
            $feed->feed_id=$request->feed_id;

            if($feed->save())
        {
            $response['status']=true;
            $response['msg']="Liked";
        }
        else{
            $response['status']=false;
            $response['msg']="Not Updated";
        }
        }
        else if($request->type=='no'){

            $res=Feed_like::where('feed_id',$request->feed_id)->where('user_id',Auth::user()->id)->delete();

            if($res)
            {
                $response['status']=true;
                $response['msg']="UnLiked";
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
        
        echo json_encode($response); 
    }
    
}
