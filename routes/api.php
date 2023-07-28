<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OptionController;
use App\Http\Controllers\OptionValueController;
use App\Http\Controllers\OptionSetController;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// products
Route::controller(ProductController::class)->group(function () {
    Route::get('/products', 'index');
    Route::post('/product/save', 'save');
    Route::post('/product/delete', 'delete');
});

// options
Route::controller(OptionController::class)->group(function () {
    Route::get('/options', 'index');
    Route::post('/option/save', 'save');
    Route::post('/option/delete', 'delete');
});

// option values
Route::controller(OptionValueController::class)->group(function () {
    Route::get('/option_values', 'index');
    Route::post('/option/value/save', 'save');
    Route::post('/option/value/delete', 'delete');
});

// option set
Route::controller(OptionSetController::class)->group(function () {
    Route::get('/option_set', 'index');
    Route::post('/option/set/save', 'save');
    Route::post('/option/set/delete', 'delete');
});


