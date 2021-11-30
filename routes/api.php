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
Route::get('get_category_vendor', [VendorController::class,'get_category_vendor']);
Route::get('get_all_category', [UserController::class,'get_all_category']);

//auth related routes
Route::post('mobile-verification', [AuthController::class,'mobile_verification']);
Route::post('otp-verification', [AuthController::class,'otp_verification']);


Route::get('unauthorized', [AuthController::class,'unauthorized']);
Route::post('add_vendor_offer', [VendorController::class,'add_vendor_offer']);

//condition  for protect the user route
Route::middleware('auth:api')->group(function () {

    // User Profile related routes
    Route::post('update_profile', [UserController::class,'update_profile']);
    Route::post('update_profile_picture', [UserController::class,'update_profile_picture']);
    Route::post('get_user_profile', [UserController::class,'get_user_profile']);
  
	Route::post('add_feed_user', [FeedController::class,'add_feed']);
	Route::post('search_all', [UserController::class,'search_all']);
	
   //vendors related Routes
    Route::post('follow_vendor_user',[UserController::class,'follow_vendor_user']);
    Route::post('get_category_vendors', [UserController::class,'get_category_vendors']);
	Route::post('sort_by', [UserController::class,'sort_by']);
	Route::post('get_vendor_details', [VendorController::class,'get_vendor_details']);
    Route::post('get_vendor_product', [VendorController::class,'get_vendor_product']);
    Route::post('update_shop_visit_contact', [VendorController::class,'update_shop_visit']);
    Route::post('get_vendor_offers', [VendorController::class,'get_vendor_offers']);

	
	Route::post('recent_view_shops', [UserController::class,'recent_view_shops']);
	
	Route::post('user_add_fevourite', [UserController::class,'user_add_favourite']);
	Route::post('user_get_fevourite', [UserController::class,'user_get_favourite']);
    
	//Feed related routes 
    Route::post('get_user_feeds', [FeedController::class,'user_feed_view']);
	Route::post('delete_feed', [FeedController::class,'delete_feed']);
    Route::post('user_feed_like', [FeedController::class,'user_feed_like']);
    Route::post('user_feed_save', [FeedController::class,'user_feed_save']);
    Route::post('update_feed_view', [FeedController::class,'update_feed_view']);
    Route::post('feed_report_user', [FeedController::class,'feed_report_user']);
    Route::get('get_feed_comment', [FeedController::class,'get_feed_comment']);
    Route::post('add_feed_comment', [FeedController::class,'add_feed_comment']);
    Route::post('edit_feed_comment', [FeedController::class,'edit_feed_comment']);
    Route::post('delete_feed_comment', [FeedController::class,'delete_feed_comment']);
    Route::post('get_saved_feed', [FeedController::class,'get_saved_feed']);
   

    //for logout 
    Route::post('logout_user', [AuthController::class,'logout']);
});



//condition  for protect the vendor route
Route::middleware('auth:vendor-api')->group(function () {
    // our routes to be protected will go in here
    Route::post('get_vendor_profile', [VendorController::class,'get_vendor_profile']);
    Route::post('update_profile_vendor', [VendorController::class,'update_profile_vendor']);
    Route::post('update_profile_picture_vendor', [VendorController::class,'update_profile_picture_vendor']);
    Route::post('update_main_category_vendor', [VendorController::class,'update_main_category_vendor']);
    Route::post('create_category_vendor', [VendorController::class,'create_category_vendor']);
    Route::post('update_store_location', [VendorController::class,'update_store_location']);
    Route::post('update_category_vendor', [VendorController::class,'update_category_vendor']);
    
	Route::post('get_cover_vendor', [VendorController::class,'get_cover_vendor']);
	Route::post('delete_cover_vendor', [VendorController::class,'delete_cover_vendor']);
	 
    Route::post('add_feed', [FeedController::class,'add_feed']);
    
	Route::post('update_cover_vendor', [VendorController::class,'update_cover_vendor']);
    Route::post('vendor_add_product', [VendorController::class,'vendor_add_product']);
    Route::post('vendor_update_product', [VendorController::class,'vendor_update_product']);
    Route::post('vendor_add_package', [VendorController::class,'vendor_add_package']);
    Route::post('vendor_update_package', [VendorController::class,'vendor_update_package']);
    Route::post('add_vendor_offer', [VendorController::class,'add_vendor_offer']);
    
    Route::post('get_vendor_data', [VendorController::class,'get_vendor_data']);
    Route::post('edit_vendor_offer', [VendorController::class,'update_vendor_offer']);
	
    Route::post('vendor_get_vendor_product', [VendorController::class,'get_vendor_product_vendor']);
	
	Route::post('update_status_product_offer', [VendorController::class,'update_status_product_offer']);
	 Route::post('get_vendor_offers_vendor', [VendorController::class,'get_vendor_offers_vendor']);
    Route::post('logout_vendor', [AuthController::class,'logout']);

});
