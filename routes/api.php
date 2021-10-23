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
});


//condition  for protect the vendor route
Route::middleware('auth:vendor-api')->group(function () {
    // our routes to be protected will go in here
    Route::post('update_profile_name2', [AuthController::class,'update_profile_name']);
});
