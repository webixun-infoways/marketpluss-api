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
use App\Models\Vendors_Subsciber;
use App\Models\Feed_Comment;
use App\Models\Feed;
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
                $response['status']=true;
                $response['profile_pic']=$path."/".$name;
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

    public function feed_view(Request $request)
    {
        $validator = Validator::make($request->all(), [ 
            'page_id' => 'required', 
        ]);

		if ($validator->fails())
    	{
        	return response(['errors'=>$validator->errors()->all()], 422);
    	}

        $user_id=Auth::user()->id;
    
        $response=Feed::addSelect(['feed_like' => Feed_like::select('feed_id')->whereColumn('feed_id', 'feeds.id') ])->orderByDesc('updated_at')->paginate($request->page_id);
        echo json_encode($response,JSON_UNESCAPED_SLASHES); 
    }


    //function for follow the vendor 

    //function for feed likes
    public function follow_vendor_user(Request $request)
    {
        $validator = Validator::make($request->all(), [ 
            'vendor_id' => 'required', 
            'type'=>'required'
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
            $response['msg']="Successful";
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
                $response['msg']="Unsubscibe";
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

    public function feed_report_user(Request $request)
    {
        $validator = Validator::make($request->all(), [ 
            'feed_id' => 'required', 
            'report'=>'required'
        ]);

		if ($validator->fails())
    	{
        	return response(['errors'=>$validator->errors()->all()], 422);
    	}

        $feed=new Feed_Report;

        $feed->feed_id=$request->feed_id;
        $feed->user_id=Auth::user()->id;
        $feed->report=$request->report;

        if($feed->save())
        {
            $response['status']=true;
            $response['msg']="Feed Successfully reported, Thankyou for the support";
        }
        else{
            $response['status']=false;
                $response['msg']="Not Updated";
        }

        return json_encode($response);
    }

    public function add_feed_comment(Request $request)
    {
        $validator = Validator::make($request->all(), [ 
            'feed_id' => 'required', 
            'comment'=>'required'
        ]);

		if ($validator->fails())
    	{
        	return response(['errors'=>$validator->errors()->all()], 422);
    	}

        $feed=new Feed_Comment;

        $feed->feed_id=$request->feed_id;
        $feed->user_id=Auth::user()->id;
        $feed->comment=$request->comment;
        $feed->status='active';
        if($feed->save())
        {
            $response['status']=true;
            $response['msg']="successful";
        }
        else{
            $response['status']=false;
                $response['msg']="Not Updated";
        }

        return json_encode($response);
    }


    public function edit_feed_comment(Request $request)
    {
        $validator = Validator::make($request->all(), [ 
            'comment_id' => 'required', 
            'comment'=>'required'
        ]);

		if ($validator->fails())
    	{
        	return response(['errors'=>$validator->errors()->all()], 422);
    	}

        $feed_comment=Feed_Comment::where('id',$request->comment_id)->where('user_id',Auth::user()->id)->update(['comment' => $request->comment]);
        if($feed_comment)
        {
            $response['status']=true;
            $response['msg']="Comment successfully updated!";
        }
        else{
            $response['status']=false;
            $response['msg']="Comment could not be updated!";
        }

        return json_encode($response);
    }
    

    public function delete_feed_comment(Request $request)
    {
        $validator = Validator::make($request->all(), [ 
            'comment_id' => 'required', 
        ]);

		if ($validator->fails())
    	{
        	return response(['errors'=>$validator->errors()->all()], 422);
    	}

        $feed_comment=Feed_Comment::where('id',$request->comment_id)->where('user_id',Auth::user()->id)->delete();
        if($feed_comment)
        {
            $response['status']=true;
            $response['msg']="Comment successfully deleted!";
        }
        else{
            $response['status']=false;
            $response['msg']="Comment could not be deleted!";
        }

        return json_encode($response);
    }

    public function get_feed_comment(Request $request)
    {
        $validator = Validator::make($request->all(), [ 
            'feed_id' => 'required',
        ]);

		if ($validator->fails())
    	{
        	return response(['errors'=>$validator->errors()->all()], 422);
    	}

        $feed=Feed_Comment::join('users','feed_comments.user_id','=','users.id')-> where('feed_id',$request->feed_id)->where('feed_comments.status','active')->select('feed_comments.comment','users.name','users.profile_pic','feed_comments.updated_at','feed_comments.user_id','feed_comments.id')->get() ;

        if(count($feed)>0)
        {
            $response['status']=true;
            $response['data']=$feed;
        }
        else{
            $response['status']=false;
                $response['msg']="no comment found.";
        }
        return json_encode($response);
    }

    //method for handling the feed save user 

    //function for feed likes
    public function feed_save(Request $request)
    {
        $validator = Validator::make($request->all(), [ 
            'feed_id' => 'required', 
            'type'=>'required'
        ]);

		if ($validator->fails())
    	{
        	return response(['errors'=>$validator->errors()->all()], 422);
    	}

        if($request->type=='save')
        {
            $feed=new Feed_Save;
            $feed->user_id=Auth::user()->id;
            $feed->feed_id=$request->feed_id;

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
        else if($request->type=='unsave'){

            $res=Feed_Save::where('feed_id',$request->feed_id)->where('user_id',Auth::user()->id)->delete();

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
        
        echo json_encode($response); 
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


        $response=Vendor::select("id", "( 3959 * acos( cos( radians(".$request->latitude.") ) * cos ( radians( shop_latitude ) ) * cos ( radians( shop_longitude ) - radians (".$request->longitude.") ) + sin ( radians(".$request->latitude.") ) * sin ( radians( shop_latitude ) ) ) ) as distance")->where('category_id',$request->category_id)->having('distance', '<', 25)->orderBy('distance')->paginate($request->page_id);
        echo json_encode($response,JSON_UNESCAPED_SLASHES); 
    }
    

}
