<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReferandEarn;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserOrderController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    echo "Welcome to MarketPluss.";
});

Route::get('/ProcessTransactionUPI', [UserOrderController::class,'ProcessTransactionUPI']);

Route::get('/send_mail', [UserController::class,'send_mail']);

Route::get('/{refer_id}',[ReferandEarn::class,'genrateRequest']);

Route::get('/VerifyOrderTransaction',[UserOrderController::class,'VerifyOrderTransaction']);
Route::post('/ResponseTransaction',function (){
    return View('ResponseTransaction');
});


// Route::get('login', [AuthController::class,'unauthorized']);
