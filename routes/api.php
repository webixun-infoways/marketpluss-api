<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\FeedController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


//Open Routes for fetch the data globally 
Route::get('fetch_home_sliders', [UserController::class,'fetch_home_sliders']);


//auth related routes
Route::post('mobile-verification', [AuthController::class,'mobile_verification']);
Route::post('otp-verification', [AuthController::class,'otp_verification']);
Route::get('unauthorized', [AuthController::class,'unauthorized']);


//condition  for protect the user route
Route::middleware('auth:api')->group(function () {

    // User Profile related routes
    Route::post('update_profile', [UserController::class,'update_profile']);
    Route::post('update_profile_picture', [UserController::class,'update_profile_picture']);
   
   //vendors related Routes
    Route::post('follow_vendor_user',[UserController::class,'follow_vendor_user']);
    Route::post('get_category_vendors', [UserController::class,'get_category_vendors']);
    Route::post('get_vendor_details', [UserController::class,'get_vendor_details']);
    Route::post('get_vendor_product', [UserController::class,'get_vendor_product']);
    
    //Feed related routes 

    Route::post('get_user_feeds', [FeedController::class,'user_feed_view']);
    Route::post('user_feed_like', [FeedController::class,'user_feed_like']);
    Route::post('user_feed_save', [FeedController::class,'user_feed_save']);
    Route::post('feed_report_user', [FeedController::class,'feed_report_user']);

    Route::post('add_feed_comment', [FeedController::class,'add_feed_comment']);
    Route::post('edit_feed_comment', [FeedController::class,'edit_feed_comment']);
    Route::post('delete_feed_comment', [FeedController::class,'delete_feed_comment']);
     
});

Route::get('get_feed_comment', [FeedController::class,'get_feed_comment']);

//condition  for protect the vendor route
Route::middleware('auth:vendor-api')->group(function () {
    // our routes to be protected will go in here
    Route::post('update_profile_vendor', [VendorController::class,'update_profile_vendor']);
    Route::post('update_profile_picture_vendor', [VendorController::class,'update_profile_picture_vendor']);
    Route::post('update_category_vendor', [VendorController::class,'update_category_vendor']);
    
});
