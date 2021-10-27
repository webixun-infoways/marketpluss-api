<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });


// Route::post('login', [UserController::class,'login']);
Route::post('mobile-verification', [AuthController::class,'mobile_verification']);
Route::post('otp-verification', [AuthController::class,'otp_verification']);

Route::get('unauthorized', [AuthController::class,'unauthorized']);



//condition  for protect the user route
Route::middleware('auth:api')->group(function () {
    // our routes to be protected will go in here
    Route::post('update_profile', [UserController::class,'update_profile']);
    Route::post('user_feed_like', [UserController::class,'feed_like']);
    Route::post('user_feed_save', [UserController::class,'feed_save']);
    Route::post('get_user_feeds', [UserController::class,'feed_view']);
    Route::post('follow_vendor_user', [UserController::class,'follow_vendor_user']);
    Route::post('feed_report_user', [UserController::class,'feed_report_user']);
    Route::post('add_feed_comment', [UserController::class,'add_feed_comment']);
    Route::post('edit_feed_comment', [UserController::class,'edit_feed_comment']);
    Route::post('delete_feed_comment', [UserController::class,'delete_feed_comment']);
    Route::post('update_profile_picture', [UserController::class,'update_profile_picture']);
    
    Route::post('get_category_vendors', [UserController::class,'get_category_vendors']);
});

Route::get('get_feed_comment', [UserController::class,'get_feed_comment']);

//condition  for protect the vendor route
Route::middleware('auth:vendor-api')->group(function () {
    // our routes to be protected will go in here
    Route::post('update_profile_vendor', [AuthController::class,'update_profile_name']);
});
