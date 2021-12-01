<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Vendor;
use App\Models\faq;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;

class FaqController extends Controller
{

    public function fetch_faq()
    {
        $faq = faq::all();
        if(count($faq) > 0){
            $response['status']=true;
           return json_encode($faq);
        }else{
            $response['status']=false;
            $response['msg'] = "FAQ Not Added!";
        //    return json_encode($response);
        }
    }
   
    public function add_faq(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'faq_title' => 'required',
            'faq_description' => 'required',
            'faq_status' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }
        $faq = new faq;
        $faq->faq_title = $request->faq_title;
        $faq->faq_description = $request->faq_description;
        $faq->faq_status = $request->faq_status;
        $faq->save();
        if($faq->save()){
            $response['status']=true;
            $response['msg'] = "FAQ Added Successfull!";
           return json_encode($response);
        }else{
            $response['status']=true;
            $response['msg'] = "FAQ Not Added!";
           return json_encode($response);
        }

    }

   
    public function edit_faq(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'faq_title' => 'required',
            'faq_description' => 'required',
            'faq_status' => 'required',
            'id' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }
        $data = [
            'faq_title' => $request->faq_title,
            'faq_description' => $request->faq_description,
            'faq_status' => $request->faq_status
           ];
        $res = faq::where('id',$request->id)->update($data);
        if($res){
            $response['status']=true;
            $response['msg'] = "FAQ Updated Successfull!";
            return json_encode($response);
        }else{
            $response['status']=true;
            $response['msg'] = "FAQ Not Updated!";
            return json_encode($response);
        }
    }

    public function delete_faq(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }

        $res = faq::destroy($request->id);
        if($res){
            $response['status']=true;
            $response['msg'] = "FAQ Deleted Successfull!";
            return json_encode($response);
        }else{
            $response['status']=true;
            $response['msg'] = "FAQ Not Deleted!";
            return json_encode($response);
        }
   
    }
}
