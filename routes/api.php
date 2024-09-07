<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ColorController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\CatalogsController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\auth\AuthController;
use App\Http\Controllers\auth\UserController;
use App\Http\Controllers\Auth\AccountController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/


Route::group([
    'middleware' => 'api',
    'prefix' => 'auth'

], function ($router) {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user-profile', [AuthController::class, 'userProfile']);
});


Route::middleware(['auth:api'])->group(function () {

    //Account Sittings
    Route::post('/change-email', [AccountController::class, 'changeEmail']);
    Route::post('/change-password', [AccountController::class, 'changePassword']);
    Route::post('/add-phone-number', [AccountController::class, 'addPhoneNumber']);


    //Catalogs
    Route::get('/catalogs', [CatalogsController::class, 'get']);
    Route::post('/catalogs', [CatalogsController::class, 'create']);
    Route::get('/catalogs/{catalog}', [CatalogsController::class, 'show']);
    Route::post('/catalogs/{catalog}', [CatalogsController::class, 'update']);
    Route::delete('/catalogs/{catalog}', [CatalogsController::class, 'delete']);
    Route::get('/catalogs/search', [CatalogsController::class, 'search']);


    //Quantity
    Route::post('/catalogs/{catalogId}/colors', [ColorController::class, 'addColor']);
    Route::get('/catalogs/{catalogId}/colors', [ColorController::class, 'getColors']);
    Route::post('/catalogs/{catalogId}/colors/update', [ColorController::class, 'updateColors']);

    //customer
    Route::get('/customers', [CustomerController::class, 'get']);
    Route::get('/customers/{customerId}', [CustomerController::class, 'show']);
    Route::post('/customers', [CustomerController::class, 'store']);
    Route::post('/customers/{customerId}', [CustomerController::class, 'update']);
    Route::delete('/customers/{customerId}', [CustomerController::class, 'destroy']);
    Route::get('/customers/search', [CustomerController::class, 'search']);

    //Orders
    Route::post('/customers/{customerId}/orders', [OrderController::class, 'store'])->middleware('role:ADMINS');
    Route::get('/customers/{customerId}/orders', [OrderController::class, 'getInfo']);
    Route::get('/customers/order/details', [OrderController::class, 'getCustomerOrders']);
    Route::post('/order/pay', [OrderController::class, 'markAsPaid']);
    Route::delete('/order/delete', [OrderController::class, 'deleteOrder']);

    Route::get('/orders', [OrderController::class, 'get']);
    Route::post('/orders/update/{orderId}', [OrderController::class, 'update'])->middleware('role:ADMINS');

    //User
    Route::post('/user/change-role', [UserController::class, 'changeUserRole']);

    //Payment
    Route::post('/orders/{orderId}/payments', [PaymentController::class, 'addPayment']);
    Route::get('/customers/{customerId}/statement', [PaymentController::class, 'getCustomerStatementByCustomerId']);
});
