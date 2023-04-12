<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;




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

// Route::get("/test",function(){
//         return "<h1>asdasd</h1>";
// });


Route::get('cron', [\App\Http\Controllers\Api\RegisterController::class, 'cron'])->name('cron');
Route::get('cron/plane', [\App\Http\Controllers\Api\RegisterController::class, 'cron_plane'])->name('cron_plane');

Route::any('register', [\App\Http\Controllers\Api\RegisterController::class, 'register']);
Route::get('noauth', [\App\Http\Controllers\Api\RegisterController::class, 'noauth'])->name('noauth');


Route::any('login', [\App\Http\Controllers\Api\RegisterController::class, 'login'])->name('login');
Route::any('verify', [\App\Http\Controllers\Api\RegisterController::class, 'verify']);
Route::post('password/email',  [\App\Http\Controllers\Api\ForgotPasswordController::class,'forget']);
Route::any('password/reset', [\App\Http\Controllers\Api\CodeCheckController::class,'index']);
Route::post('password/code/check', [\App\Http\Controllers\Api\CodeCheckController::class,'code_verify']);
Route::get('guide', [\App\Http\Controllers\Api\CMSController::class, 'guide']);
Route::get('term/conditions', [\App\Http\Controllers\Api\CMSController::class, 'termanscondition']);

Route::group(['middleware' => ['api','auth:api'], 'prefix' => 'auth'], function () {
// Route::middleware('auth:api')->group( function () {
    Route::get('notification', [\App\Http\Controllers\Api\UserController::class, 'un_reead_notification']); 
    Route::post('/notification',[\App\Http\Controllers\Api\UserController::class,'read_notification']);
    Route::post('/checkout', [App\Http\Controllers\Api\OrderController::class, 'store']);
    Route::get('shipping', [\App\Http\Controllers\Api\ShippingController::class, 'index']); 
    Route::get('category', [\App\Http\Controllers\Api\CategoryController::class, 'index']); 
    Route::get('brand', [\App\Http\Controllers\Api\BrandController::class, 'index']); 
    Route::get('product', [\App\Http\Controllers\Api\ProductController::class, 'index']); 
    Route::get('product/{brand}', [\App\Http\Controllers\Api\ProductController::class, 'brand_product']); 
	Route::resource('cart',App\Http\Controllers\Api\CartController::class);
	
	Route::resource('trophy',App\Http\Controllers\Api\TrophyController::class);
    Route::post('set_goal', [\App\Http\Controllers\Api\GoalController::class, 'set_goal']); 
    Route::get('goal/list', [\App\Http\Controllers\Api\GoalController::class, 'list']); 
    Route::post('addcard', [\App\Http\Controllers\UserCardController::class, 'addcard']);
	Route::post('updatecard', [\App\Http\Controllers\UserCardController::class, 'updatecard']); 
    Route::get('me', [\App\Http\Controllers\Api\RegisterController::class, 'me']);
    Route::get('user', [\App\Http\Controllers\Api\RegisterController::class, 'user']);
    Route::get('orders', [\App\Http\Controllers\Api\OrderController::class, 'orders']);
    Route::get('children_orders', [\App\Http\Controllers\Api\OrderController::class, 'childorders']);
    Route::post('order/{status}', [\App\Http\Controllers\Api\OrderController::class, 'orders_status']);
    Route::get('transaction', [\App\Http\Controllers\TranasactionController::class, 'index']);
    Route::post('withdraw', [\App\Http\Controllers\TranasactionController::class, 'store']);
    Route::get('withdraw/list', [\App\Http\Controllers\TranasactionController::class, 'index']);
    Route::post('change_password', [\App\Http\Controllers\Api\RegisterController::class, 'change_password']); 
    Route::post('profile', [\App\Http\Controllers\Api\UserController::class, 'profile']);  
	Route::post('cuurent/plan', [\App\Http\Controllers\Api\UserController::class, 'current_plan']);  	
    Route::get('financial/breakdowns/{date}', [\App\Http\Controllers\Api\FinancialController::class, 'financialdata']);
    Route::post('financial/breakdowns/post', [\App\Http\Controllers\Api\FinancialController::class, 'financialpost']);
    Route::get('admin/info', [\App\Http\Controllers\Api\ContactController::class, 'admininfo']);
    Route::post('contact/submit', [\App\Http\Controllers\Api\ContactController::class, 'contact_info']);
    Route::get('logout', [\App\Http\Controllers\Api\RegisterController::class, 'logout']);
});
