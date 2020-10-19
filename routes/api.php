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

Route::middleware('api')->group(function () {
    Route::get('/freeSpaces', 'Parking\ActionController@getFreeSpaces');
    Route::post('/enterParking', 'Parking\ActionController@enterParking');
    Route::post('/exitParking', 'Parking\ActionController@exitParking');
    Route::get('/checkCost', 'Parking\ActionController@checkCost');

});
