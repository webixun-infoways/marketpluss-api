<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FeedController extends Controller
{
    
    //function for feed likes
    public function user_feed_like(Request $request)
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

    public function user_feed_view(Request $request)
    {
        $validator = Validator::make($request->all(), [ 
            'page_id' => 'required', 
            'vendor_id'=>'required'
        ]);

		if ($validator->fails())
    	{
        	return response(['errors'=>$validator->errors()->all()], 422);
    	}


        if($request->vendor_id==0)
        {
            $response=Feed::addSelect(['feed_like' => Feed_like::select('feed_id')->whereColumn('feed_id', 'feeds.id') ])->orderByDesc('updated_at')->paginate($request->page_id);
        }
        else{
            $response=Feed::addSelect(['feed_like' => Feed_like::select('feed_id')->whereColumn('feed_id', 'feeds.id') ])->where('vendor_id','=',$request->vendor_id)->orderByDesc('updated_at')->paginate($request->page_id);
        }
        
    
        echo json_encode($response,JSON_UNESCAPED_SLASHES); 
    }

    //function for follow the vendor   
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
    public function user_feed_save(Request $request)
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


}