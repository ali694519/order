<?php

use App\Http\Controllers\Auth\AccountController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CatalogsController;
use App\Http\Controllers\ColorController;
use App\Http\Controllers\auth\AuthController;

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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });


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

    Route::post('/change-email', [AccountController::class, 'changeEmail']);
    Route::post('/change-password', [AccountController::class, 'changePassword']);
    Route::post('/add-phone-number', [AccountController::class, 'addPhoneNumber']);


    //Catalogs
    Route::get('/catalogs', [CatalogsController::class, 'get']);
    Route::post('/catalogs', [CatalogsController::class, 'create']);
    Route::get('/catalog/{catalog}', [CatalogsController::class, 'show']);
    Route::post('/catalogs/{catalog}', [CatalogsController::class, 'update']);
    Route::delete('/catalogs/{catalog}', [CatalogsController::class, 'delete']);

    //Quantity
    Route::post('/catalogs/{catalogId}/colors', [ColorController::class, 'addColor']);
    Route::get('/catalogs/{catalogId}/colors', [ColorController::class, 'getColors']);
    Route::post('/catalogs/{catalogId}/colors/update', [ColorController::class, 'updateColors']);

    //clients
    Route::get('/customers', [CustomerController::class, 'get']);
    Route::get('/customers/{customerId}', [CustomerController::class, 'show']);
    Route::post('/customers', [CustomerController::class, 'store']);
    Route::post('/customers/{customerId}', [CustomerController::class, 'update']);
    Route::delete('/customers/{customerId}', [CustomerController::class, 'destroy']);

    //Orders
    Route::post('/clients/{clientId}/orders', [OrderController::class, 'store']);
});
