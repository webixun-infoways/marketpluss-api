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
use App\Models\point_level;
use App\Models\Vendor_Product;
use App\Models\Vendors_Subsciber;
use App\Models\user_txn_log;
use App\Models\Vendor_category;
use App\Models\Feed_Comment;
use App\Models\Feed;
use App\Models\Slider;
use App\Models\Vendor_cover;
use App\Models\feed_content;
use App\Models\Notification;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use App\Jobs\ProcessPush;
use App\Http\Controllers\UserTransactionController;
use App\Http\Controllers\GlobalController;
use Storage;
class FeedController extends Controller
{
    public function delete_feed(Request $request){
		//return $request;
		 $validator = Validator::make($request->all(), [ 
            'feed_id' => 'required', 
			'vendor_id' => 'required', 
        ]);
		//return Auth::user()->id;

		if ($validator->fails())
    	{
        	return response(['errors'=>$validator->errors()->all()], 422);
    	}

        $feed=Feed::where('id',$request->feed_id)->where('vendor_id',Auth::user()->id)->update(['feed_status'=>'delete']);
        if($feed)
        {
            $current_pic=feed_content::where('feed_id',$request->feed_id)->get(['id','content_src']);
            foreach($current_pic as $pic)
            {
                $pic_id=$pic->id;
                $res = feed_content::where('id',$pic_id)->update(['content_status'=>'delete']);
                 //code for delete the file from storage
                if($res){
                    $nf= str_replace(env('APP_CDN_URL'),'',$pic->content_src);
                    Storage::disk(env('DEFAULT_STORAGE'))->delete($nf);
                }
               
            }
            
            $response['status']=true;
            $response['msg']="Feed successfully deleted!";
        }
        else{
            $response['status']=false;
            $response['msg']="Feed could not be deleted!";
        }

        return json_encode($response);
	}
    //function for feed likes
    public function add_feed(Request $request)
    {
        $validator = Validator::make($request->all(), [ 
            //'title' => 'required', 
            'description'=>'required',
			'user_type'=> 'required',
			'tag_id'=>'required'
        ]);

		if ($validator->fails())
    	{
        	return response(['errors'=>$validator->errors()->all()], 422);
    	}

        
        //return Auth::user()->id;
        $feed=new Feed;
        //$feed->feed_title=$request->title;
        $feed->feed_description=$request->description;
        $feed->feed_status='active';
        $feed->vendor_id=Auth::user()->id;
		$feed->user_type=$request->user_type;
		$feed->tag_id=$request->tag_id;
        if($feed->save())
        {
            $last_feed_id=$feed->id;
            $response['status']=true;
			$files=$request->file('feed_file');
            if(is_array($files)){
                $data=array();
               foreach($files as $file){
                   //create unique name of file uploaded.
                    
					$globalclass=new GlobalController();
					$path = 'feed_img/';
				
						$res=$globalclass->upload_img($file,$path);
				
						if(!$res['status'])
						{
							$response['status']=false;
							$response['msg']="Not Updated";
							return json_encode($response);
						}
						else
						{
							$link = $res['file_name'];
							  $data[] = ['feed_id'=>$feed->id, 'content_src'=> $link,'content_type' => 'image'];
						}
				}

               
            }
			else
			{
				 //create unique name of file uploaded.
                 
                    
					$globalclass=new GlobalController();
					$path = 'feed_img/';
				
						$res=$globalclass->upload_img($files,$path);
				
						if(!$res['status'])
						{
							$response['status']=false;
							$response['msg']="Not Updated";
							return json_encode($response);
						}
						else
						{
							$link = $res['file_name'];
							  $data[] = ['feed_id'=>$feed->id, 'content_src'=> $link,'content_type' => 'image'];
						}
			}
			
			
			 if(feed_content::insert($data))
                {
                    $response['status']=true;
                    $response['msg']="Category Successfully Updated!";
                }
                else{
                    Feed::where('id',$feed->id)->delete();
                    $response['status']=false;
                    $response['msg']="Category could not be updated!";
                }
			
		    $user_id=Auth::user()->id;
			$last_added_data=Feed::with('feed_content')
			->addSelect(['user_name' => User::select('name')->whereColumn('id', 'feeds.vendor_id')->where('feeds.user_type','user'),'user_profile_pic' => User::select('profile_pic')->whereColumn('id', 'feeds.vendor_id')->where('feeds.user_type','user')])
			->addSelect(['shop_name' => Vendor::where('status','Active')->select('shop_name')->whereColumn('id', 'feeds.vendor_id')->where('feeds.user_type','vendor'),'vendor_name' => Vendor::where('status','Active')->select('name')->whereColumn('id', 'feeds.tag_id'),'vendor_profile' => Vendor::where('status','Active')->select('profile_pic')->whereColumn('id', 'feeds.tag_id'),'vendor_area' => Vendor::where('status','Active')->select('area')->whereColumn('id', 'feeds.tag_id')])
			->addSelect(['feed_like' => Feed_like::select('feed_id')->whereColumn('feed_id', 'feeds.id')->where('user_id',$user_id)])
			->addSelect(['feed_save' => Feed_Save::select('feed_id')->whereColumn('feed_id', 'feeds.id')->where('user_id',$user_id)])->where('feed_status','active')
			->where('feeds.id',$last_feed_id)->orderBy('id','desc')->first();

            $response['msg']= "Feed added!";
			
			$response['last_added_data']= $last_added_data;
			
            if($request->user_type != 'vendor')
            {
                //Cashback Initiated
			$permission=new UserTransactionController();
			$coin = point_level::get();
            // return $coin;
			$today_earning = user_txn_log::where('user_id',Auth::user()->id)->whereDate('created_at',date('Y-m-d'))->sum('txn_amount');
			if($today_earning <= $coin[0]->max_point_per_day){
				$heading_user= $coin[0]->feed_points." MP coins has been initiated to your wallet for the feed review.";
				$permission->credit_coin($user_id,$heading_user,$coin[0]->feed_points,'success','credit');
				$post_url="https://marketpluss.com/";
				ProcessPush::dispatch($heading_user,$post_url,$user_id,'user','');
			}

            }
			
			
        }
        else{
            $response['status']=false;
            $response['msg']="Not Updated";
        }

        echo json_encode($response,JSON_UNESCAPED_SLASHES); 
    }


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
			Feed_like::where('feed_id',$request->feed_id)->where('user_id',Auth::user()->id)->delete();
            $feed=new Feed_like;
            $feed->user_id=Auth::user()->id;
            $feed->feed_id=$request->feed_id;

            if($feed->save())
        {
            $response['status']=true;
            $response['msg']="Liked";
			
			
			//notification details 
			$heading_user= Auth::user()->name." liked your post";
			$f_data=Feed::where('id',$request->feed_id)->get(['vendor_id','user_type','tag_id']);
			
			$tag_id=$f_data[0]->tag_id;
			$user_type=$f_data[0]->user_type;
			$user_id=$f_data[0]->vendor_id;
			
			if($user_type=='user')
			{
				$post_url=env('NOTIFICATION_USER_URL')."/feedView/".$request->feed_id;
			}
			else{
				$post_url=env('NOTIFICATION_VENDOR_URL')."/feedView/".$request->feed_id;
			}
			
		
			ProcessPush::dispatch($heading_user,$post_url,$user_id,$user_type,'');
			
			if($user_type=='user')
			{
				$post_url=env('NOTIFICATION_VENDOR_URL')."/feedView/".$request->feed_id;
				$heading_user= Auth::user()->name." liked your post";
				
			
				ProcessPush::dispatch($heading_user,$post_url,$tag_id,'vendor','');
			
			}
			
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
		//return $request;
        $validator = Validator::make($request->all(), [ 
           'vendor_id'=>'required',
			'action_type'=>'required'
        ]);

		if ($validator->fails())
    	{
        	return response(['errors'=>$validator->errors()->all()], 422);
    	}

		$type=$request->action_type;
		
		$vendor_id=$request->vendor_id;
		 $user_id=Auth::user()->id;
		 
		 
        if($request->vendor_id==0 && $type=='all')
        {
			$response=Feed::with('feed_content')->withCount('feed_like')->withCount('feed_comment')
			->addSelect(['user_name' => User::select('name')->whereColumn('id', 'feeds.vendor_id')->where('feeds.user_type','user'),'user_profile_pic' => User::select('profile_pic')->whereColumn('id', 'feeds.vendor_id')->where('feeds.user_type','user')])
			->addSelect(['shop_name' => Vendor::where('status','Active')->select('shop_name')->whereColumn('id', 'feeds.vendor_id')->where('feeds.user_type','vendor'),'vendor_profile_pic' => Vendor::where('status','Active')->select('profile_pic')->whereColumn('id', 'feeds.vendor_id')->where('feeds.user_type','vendor')])
			->addSelect(['feed_like' => Feed_like::select('feed_id')->whereColumn('feed_id', 'feeds.id')->where('user_id',$user_id)])
			->addSelect(['feed_save' => Feed_Save::select('feed_id')->whereColumn('feed_id', 'feeds.id')->where('user_id',$user_id)])->where('feed_status','active')
			->orderByDesc('updated_at')->paginate(10);
        }
		else if ($request->vendor_id==0 && $type!='all')
		{
			$response=Feed::with('feed_content')->withCount('feed_like')->withCount('feed_comment')
			->addSelect(['user_name' => User::select('name')->whereColumn('id', 'feeds.vendor_id')->where('feeds.user_type','user'),'user_profile_pic' => User::select('profile_pic')->whereColumn('id', 'feeds.vendor_id')->where('feeds.user_type','user')])
			->addSelect(['shop_name' => Vendor::where('status','Active')->select('shop_name')->whereColumn('id', 'feeds.vendor_id')->where('feeds.user_type','vendor'),'vendor_profile_pic' => Vendor::where('status','Active')->select('profile_pic')->whereColumn('id', 'feeds.vendor_id')->where('feeds.user_type','vendor')])
			->addSelect(['feed_like' => Feed_like::select('feed_id')->whereColumn('feed_id', 'feeds.id')])
			->addSelect(['feed_save' => Feed_Save::select('feed_id')->whereColumn('feed_id', 'feeds.id')->where('user_id',$user_id)])
			->where('user_type',$type)->where('feed_status','active')->orderByDesc('updated_at')->paginate(10);
			
		}
		else if ($request->vendor_id!=0 && $type=='all')
			{
			$response=Feed::with('feed_content')->withCount('feed_like')->withCount('feed_comment')
			->addSelect(['user_name' => User::select('name')->whereColumn('id', 'feeds.vendor_id')->where('feeds.user_type','user'),'user_profile_pic' => User::select('profile_pic')->whereColumn('id', 'feeds.vendor_id')->where('feeds.user_type','user')])
			->addSelect(['shop_name' => Vendor::where('status','Active')->select('shop_name')->whereColumn('id', 'feeds.vendor_id')->where('feeds.user_type','vendor'),'vendor_profile_pic' => Vendor::where('status','Active')->select('profile_pic')->whereColumn('id', 'feeds.vendor_id')->where('feeds.user_type','vendor')])
			->addSelect(['feed_like' => Feed_like::select('feed_id')->whereColumn('feed_id', 'feeds.id')->where('user_id',$user_id)])
			->addSelect(['feed_save' => Feed_Save::select('feed_id')->whereColumn('feed_id', 'feeds.id')->where('user_id',$user_id)])
			->where('feed_status','active')
			->where('vendor_id',$vendor_id)->orderByDesc('updated_at')->paginate(10);
		}
		else
		{
			
			$response=Feed::with('feed_content')->withCount('feed_like')->withCount('feed_comment')
			->addSelect(['user_name' => User::select('name')->whereColumn('id', 'feeds.vendor_id')->where('feeds.user_type','user'),'user_profile_pic' => User::select('profile_pic')->whereColumn('id', 'feeds.vendor_id')->where('feeds.user_type','user')])
			->addSelect(['shop_name' => Vendor::where('status','Active')->select('shop_name')->whereColumn('id', 'feeds.vendor_id')->where('feeds.user_type','vendor'),'vendor_profile_pic' => Vendor::where('status','Active')->select('profile_pic')->whereColumn('id', 'feeds.vendor_id')->where('feeds.user_type','vendor')])
			->addSelect(['feed_like' => Feed_like::select('feed_id')->whereColumn('feed_id', 'feeds.id')->where('user_id',$user_id)])
			->addSelect(['feed_save' => Feed_Save::select('feed_id')->whereColumn('feed_id', 'feeds.id')->where('user_id',$user_id)])
			->where('user_type',$type)->where('feed_status','active')
			->where('vendor_id',$vendor_id)->where('user_type',$type)->orderByDesc('updated_at')->paginate(10);
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
			
			//notification details 
			$heading_user= Auth::user()->name." Reported your post.";
			$fdata = Feed::find($request->feed_id);
			$receiver_id=$fdata->vendor_id;
			$user_id=Auth::user()->id;
			$user_type="vendor";
			$post_url=env('NOTIFICATION_VENDOR_URL')."/reported_feed/". Auth::user()->id;
			
			
			ProcessPush::dispatch($heading_user,$post_url,$user_id,$user_type,'');
        }
        else{
            $response['status']=false;
                $response['msg']="Not Updated";
        }

        return json_encode($response);
    }
	
	
	//get single feed for user
	 
    public function get_single_feed(Request $request)
    {
        $validator = Validator::make($request->all(), [ 
            'feed_id' => 'required',
        ]);

		if ($validator->fails())
    	{
        	return response(['errors'=>$validator->errors()->all()], 422);
    	}

		$feed_id=$request->feed_id;
		$user_id=Auth::user()->id;
        $feed=Feed::with('feed_content')->withCount('feed_like')
			->addSelect(['user_name' => User::select('name')->whereColumn('id', 'feeds.vendor_id')->where('feeds.user_type','user'),'user_profile_pic' => User::select('profile_pic')->whereColumn('id', 'feeds.vendor_id')->where('feeds.user_type','user')])
			->addSelect(['shop_name' => Vendor::where('status','Active')->select('shop_name')->whereColumn('id', 'feeds.vendor_id')->where('feeds.user_type','vendor'),'vendor_profile_pic' => Vendor::where('status','Active')->select('profile_pic')->whereColumn('id', 'feeds.vendor_id')->where('feeds.user_type','vendor')])
			->addSelect(['feed_like' => Feed_like::select('feed_id')->whereColumn('feed_id', 'feeds.id')->where('user_id',$user_id)])
			->addSelect(['feed_save' => Feed_Save::select('feed_id')->whereColumn('feed_id', 'feeds.id')->where('user_id',$user_id)])->where('feed_status','active')
			->where('id',$feed_id)->orderByDesc('updated_at')->get();
	
      
            $response['status']=true;
            $response['data']=$feed;
    

        return json_encode($response);
    }
	
	//function for follow the vendor  
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
			
			//notification details 
			$heading_user= Auth::user()->name." commented on your post.";
			$f_data=Feed::where('id',$request->feed_id)->get(['vendor_id','user_type','tag_id']);
			
			$tag_id=$f_data[0]->tag_id;
			$user_type=$f_data[0]->user_type;
			$user_id=$f_data[0]->vendor_id;
			
			$post_url=env('NOTIFICATION_USER_URL')."/feedComment/".$request->feed_id;
			
			
			
			ProcessPush::dispatch($heading_user,$post_url,$user_id,$user_type,'');
			
			if($user_type=='user')
			{
				$post_url=env('NOTIFICATION_VENDOR_URL')."/feedComment/".$request->feed_id;
				$heading_user= Auth::user()->name." commented on a post.";
				
				ProcessPush::dispatch($heading_user,$post_url,$tag_id,'vendor','');
			
			}
        }
        else{
            $response['status']=false;
                $response['msg']="Not Updated";
        }

        return json_encode($response);
    }
	
	public function reply_feed_comment(Request $request)
    {
        $validator = Validator::make($request->all(), [ 
            'feed_id' => 'required', 
            'reply'=>'required',
			'comment_id'=>'required',
        ]);

		if ($validator->fails())
    	{
        	return response(['errors'=>$validator->errors()->all()], 422);
    	}

        $feed=new Feed_Comment;

        $feed->feed_id=$request->feed_id;
        $feed->vendor_id=Auth::user()->id;
        $feed->reply=$request->reply;
		$feed->parent_id=$request->comment_id;
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
		//$feed_comment=Feed_Comment::where('id',$request->comment_id)->delete();
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
		//return $request->feed_id;
        $validator = Validator::make($request->all(), [ 
            'feed_id' => 'required'
        ]);

		if ($validator->fails())
    	{
        	return response(['errors'=>$validator->errors()->all()], 422);
    	}
		
        $feed = Feed_comment::join('users','feed_comments.user_id','=','users.id')
		->where('feed_id',$request->feed_id)->where('parent_id',0)
		->where('feed_comments.status','active')
		->get(['feed_comments.comment','users.name','users.profile_pic','feed_comments.updated_at','feed_comments.user_id','feed_comments.id']);
		//return $feed;
		
	//run foraech 
	
	//fetch replyes for perticular commnet
	 foreach($feed as $key=>$o)
		{
			 $offer_id=$o->id;
			$feed[$key]['reply']=Feed_Comment::join('vendors','feed_comments.vendor_id','=','vendors.id')
			->whereIn('feed_comments.id',function($q) use($offer_id){
			    $q->from('feed_comments')->select('id')->where('parent_id',$offer_id);
			})
			->get(['feed_comments.reply','vendors.name','vendors.profile_pic','feed_comments.updated_at','feed_comments.vendor_id','feed_comments.id']);
		}

//return $feed;
        if(count($feed)>0)
        {
            $response['status']=true;
            $response['data']=$feed;
        }
        else{
            $response['status']=false;
                $response['msg']="no comment found.";
        }
        return json_encode($response,JSON_UNESCAPED_SLASHES);
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
			Feed_Save::where('feed_id',$request->feed_id)->where('user_id',Auth::user()->id)->delete();
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

    //function for update feed view
    public function update_feed_view(Request $request)
    {
        $validator = Validator::make($request->all(), [ 
            'feed_id' => 'required', 
        ]);

		if ($validator->fails())
    	{
        	return response(['errors'=>$validator->errors()->all()], 422);
    	}


        $user_id=Auth::user()->id;

        $feed=Feed::find($request->feed_id);

        $feed->feed_view=$feed->feed_view+1;

        if($feed->save())
        {
            $response['status']=true;
            $response['msg']="updated";
        }
        else{
            $response['status']=false;
            $response['msg']="not updated";
        }
        return json_encode($response);
    }
    
	
	//function for update feed view
    public function get_saved_feeds(Request $request)
    {
       $user_id=Auth::user()->id;
        $data=Feed::with('feed_content')->withCount('feed_like')
		->addSelect(['user_name' => User::select('name')->whereColumn('id', 'feeds.vendor_id')->where('feeds.user_type','user'),'user_profile_pic' => User::select('profile_pic')->whereColumn('id', 'feeds.vendor_id')->where('feeds.user_type','user')])
			->addSelect(['shop_name' => Vendor::where('status','Active')->select('shop_name')->whereColumn('id', 'feeds.vendor_id')->where('feeds.user_type','vendor'),'vendor_profile_pic' => Vendor::where('status','Active')->select('profile_pic')->whereColumn('id', 'feeds.vendor_id')->where('feeds.user_type','vendor')])
			->addSelect(['feed_like' => Feed_like::select('feed_id')->whereColumn('feed_id', 'feeds.id') ])->whereIn('feeds.id',function($q)use($user_id){
				$q->from('feed_saves')->selectRaw('feed_id')->where('user_id', $user_id);
		})->where('feed_status','active')->get();
        
		$response['status']=true;
		$response['data']=$data;
        return json_encode($response,JSON_UNESCAPED_SLASHES);
	}
}
