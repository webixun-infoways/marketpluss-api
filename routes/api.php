<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\UserOrderController;
use App\Http\Controllers\UserTransactionController;

//Open Routes for fetch the data globally 
Route::get('fetch_home_sliders', [UserController::class,'fetch_home_sliders']);
Route::get('get_category_vendor', [VendorController::class,'get_category_vendor']);


Route::get('get_all_category', [UserController::class,'get_all_category']);
Route::get('send_mail', [UserController::class,'send_mail']);
//auth related routes
Route::post('mobile-verification', [AuthController::class,'mobile_verification']);
Route::post('otp-verification', [AuthController::class,'otp_verification']);


Route::get('unauthorizeds', [AuthController::class,'unauthorized']);
Route::post('add_vendor_offer', [VendorController::class,'add_vendor_offer']);
Route::get('fetch_home_data', [HomeController::class,'fetch_home_data']);
Route::post('get_single_feed', [FeedController::class,'get_single_feed']);
//Route Faq Controller
// Route::post('add_faq', [FaqController::class,'add_faq']);
// Route::post('edit_faq', [FaqController::class,'edit_faq']);
// Route::post('delete_faq', [FaqController::class,'delete_faq']);
Route::get('fetch_faq', [FaqController::class,'fetch_faq']);

Route::post('valiate_upi_id',[AuthController::class,'validate_upi_id']);

//condition  for protect the user route
Route::middleware('auth:api')->group(function () {
	
	Route::post('fetch_user_notification', [UserController::class,'fetch_user_notification']);
	   
    // User Profile related routes
    Route::post('update_profile', [UserController::class,'update_profile']);
    Route::post('update_profile_picture', [UserController::class,'update_profile_picture']);
    Route::post('get_user_profile', [UserController::class,'get_user_profile']);
  
	Route::post('add_feed_user', [FeedController::class,'add_feed']);
	Route::post('search_all', [UserController::class,'search_all']);
	
	//user related route
	 Route::post('fetch_user_profile_different', [UserController::class,'fetch_user_profile_different']);
     Route::post('fetch_payment_methods', [UserController::class,'fetch_payment_methods']);
     
   //vendors related Routes
    Route::post('follow_vendor_user',[UserController::class,'follow_vendor_user']);
    Route::post('get_category_vendors', [UserController::class,'get_category_vendors']);
	Route::post('get_vendor_details', [VendorController::class,'get_vendor_details']);
    Route::post('get_vendor_product', [VendorController::class,'get_vendor_product']);
    Route::post('update_shop_visit_contact', [VendorController::class,'update_shop_visit']);
    Route::post('get_vendor_offers', [VendorController::class,'get_vendor_offers']);
    Route::post('get_vendor_offers_single', [VendorController::class,'get_vendor_offers_single']);
	
	Route::post('recent_view_shops', [UserController::class,'recent_view_shops']);
	
	Route::post('user_add_fevourite', [UserController::class,'user_add_favourite']);
	Route::post('user_get_fevourite', [UserController::class,'user_get_favourite']);
    Route::post('get_vendors_for_payment',[UserOrderController::class,'get_vendors_for_payment']);
	//Feed related routes 
    Route::post('get_user_feeds', [FeedController::class,'user_feed_view']);
	Route::post('delete_feed', [FeedController::class,'delete_feed']);
	Route::get('get_feed_comment', [FeedController::class,'get_feed_comment']);
    Route::post('user_feed_like', [FeedController::class,'user_feed_like']);
    Route::post('user_feed_save', [FeedController::class,'user_feed_save']);
    Route::post('update_feed_view', [FeedController::class,'update_feed_view']);
    Route::post('feed_report_user', [FeedController::class,'feed_report_user']);
    
    Route::post('add_feed_comment', [FeedController::class,'add_feed_comment']);
	Route::post('reply_feed_comment', [FeedController::class,'reply_feed_comment']);
    Route::post('edit_feed_comment', [FeedController::class,'edit_feed_comment']);
    Route::post('delete_feed_comment', [FeedController::class,'delete_feed_comment']);
    Route::post('get_saved_feeds', [FeedController::class,'get_saved_feeds']);
	Route::post('get_single_feed_user',[FeedController::class,'get_single_feed']);
	
	Route::post('get_earn_data',[UserController::class,'get_earn_data']);
	Route::post('get_vendor_data_using_code',[VendorController::class,'get_vendor_data_using_code']);
	Route::post('give_vendor_rating',[UserController::class,'vendor_rating']);
	Route::post('user_get_vendor_reviews',[UserController::class,'user_get_vendor_reviews']);
	Route::post('get_vendor_reviews',[UserController::class,'get_vendor_reviews']);
	Route::post('get_user_transations',[UserController::class,'get_user_transations']);

	Route::post('transferTobank',[UserTransactionController::class,'transfer_to_bank']);
    Route::post('verifyTransaction',[UserTransactionController::class,'verifyTransaction']);
    
    Route::post('fetch_orders_user',[UserOrderController::class,'fetch_orders_user']);
    Route::post('request_cashback_order',[UserOrderController::class,'request_cashback_order']);
    Route::post('fetch_cashback_order_details_user',[UserOrderController::class,'fetch_cashback_order_details_user']);
    Route::post('calculate_order_discount',[UserOrderController::class,'calculate_order_discount']);
    Route::post('payonlineorder',[UserOrderController::class,'payonlineorder']);
    Route::post('initiateOrderTransaction',[UserOrderController::class,'initiateOrderTransaction']);
    Route::post('initiateOrderTransaction2',[UserOrderController::class,'initiateOrderTransaction2']);
    Route::post('VerifyOrderTransaction',[UserOrderController::class,'VerifyOrderTransaction']);
    Route::get('fetch-top-category',[UserController::class,'fetch_top_category']);
   
   
    //user following routes
    Route::post('follow_user',[UserController::class,'follow_user']);

  //for logout 
    Route::post('logout_user', [AuthController::class,'logout']);
});



//condition  for protect the vendor route
Route::middleware('auth:vendor-api')->group(function () {
	Route::post('update_store_timing', [VendorController::class,'update_store_timing']);
	Route::post('get_store_timing', [VendorController::class,'get_store_timing']);
    Route::post('edit_category', [VendorController::class,'edit_category']);
	Route::post('fetch_vendor_notification', [VendorController::class,'fetch_vendor_notification']);
	Route::post('get_user_feeds_vendor', [FeedController::class,'user_feed_view']);
	Route::post('delete_feed_vendor', [FeedController::class,'delete_feed']);
	Route::post('reply_feed_comment_vendor', [FeedController::class,'reply_feed_comment']);
    //Route::post('delete_feed_comment_vendor', [FeedController::class,'delete_feed_comment']);
	Route::get('get_feed_comment_vendor', [FeedController::class,'get_feed_comment']);
    // our routes to be protected will go in here
    Route::post('get_vendor_profile', [VendorController::class,'get_vendor_profile']);
    Route::post('update_profile_vendor', [VendorController::class,'update_profile_vendor']);
    Route::post('update_profile_picture_vendor', [VendorController::class,'update_profile_picture_vendor']);
    Route::post('update_main_category_vendor', [VendorController::class,'update_main_category_vendor']);
    Route::post('create_category_vendor', [VendorController::class,'create_category_vendor']);
    Route::post('update_store_location', [VendorController::class,'update_store_location']);
    Route::post('update_category_vendor', [VendorController::class,'update_category_vendor']);
    Route::post('get_selected_category_vendor', [VendorController::class,'get_selected_category_vendor']);
	
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
    Route::post('get_orders_vendor', [VendorController::class,'get_orders_vendor']);
    
    Route::post('edit_vendor_offer', [VendorController::class,'update_vendor_offer']);
	
    Route::post('vendor_get_vendor_product', [VendorController::class,'get_vendor_product_vendor']);
	Route::post('delete_feed_comment_vendor', [FeedController::class,'delete_feed_comment']);
	Route::post('update_status_product_offer', [VendorController::class,'update_status_product_offer']);
	Route::post('get_vendor_offers_vendor', [VendorController::class,'get_vendor_offers_vendor']);
	Route::post('vendorReviewsRating',[VendorController::class,'vendorReviewsRating']); 
	Route::post('get_single_feed_vendor',[FeedController::class,'get_single_feed']);
	Route::get('vendor_shop_visit',[VendorController::class,'vendor_shop_visit']);
	Route::get('get_vendor_follower',[VendorController::class,'get_vendor_follower']);
	Route::get('get_contacts_detail',[VendorController::class,'get_contacts_detail']);
	Route::get('get_saved_feed_user_detail',[VendorController::class,'get_saved_feed_user_detail']);
    Route::post('verify_order_id',[VendorController::class,'verify_order_id']);
    Route::post('update_order_status',[VendorController::class,'update_order_status']);
	Route::post('update_flat_deals',[VendorController::class,'update_flat_deals']);
    Route::post('get_orders_details_vendor',[VendorController::class,'get_orders_details_vendor']);
    
    Route::post('logout_vendor', [AuthController::class,'logout']);

});
