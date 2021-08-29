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
Route::get('/balance', 'App\Http\Controllers\ApiController@balance');
Route::post('/evento', 'App\Http\Controllers\ApiController@handler');
Route::post('/reset', 'App\Http\Controllers\ApiController@store');
