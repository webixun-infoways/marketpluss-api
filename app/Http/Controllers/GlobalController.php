<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Storage;


class GlobalController extends Controller
{
  public function removeprevious()
  {
    $current_pic=Auth::user()->profile_pic;
    //code for delete the file from storage
    $nf= str_replace(env('APP_CDN_URL'),'',$current_pic);
    Storage::disk(env('DEFAULT_STORAGE'))->delete($nf);
  }
  public function upload_img($file,$path)
  {
    $file_name = time().'.'.$file->getClientOriginalExtension();
    $res = Storage::disk(env('DEFAULT_STORAGE'))->put($path.$file_name,file_get_contents($file));
    if($res){
      $response['status']=true;
      $response['file_name']=env('APP_CDN_URL').$path.$file_name;
    }else{
      $response['status']=false;
    }
    return $response;
  }
	
	
    public function upload_files($files,$feed_id,$path)
    {
      $data=array();
      foreach($files as $file){
          //create unique name of file uploaded.
          $name=time().'_'.$file->getClientOriginalName();
          $res = Storage::disk('shared')->put($path.'/'.$name,file_get_contents($file));
          if($res)
          {
              $data[] = ['feed_id'=>$feed_id, 'content_src'=> $path."/".$name,'content_type' => 'image'];
          }
      }
      $res = feed_content::insert($data);
      if($res){
        return true;
      }
      else{
        return false;
      }
    }
}
