<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::post('vote','App\Http\Controllers\CatAPIController@vote')->middleware('api.auth');
Route::post('my-votes','App\Http\Controllers\CatAPIController@getVotesByUserId')->middleware('api.auth');
Route::post('votes-summary','App\Http\Controllers\CatAPIController@getVotesSummary')->middleware('api.auth');
Route::post('reset-votes','App\Http\Controllers\CatAPIController@createCatAPIVoting')->middleware('api.auth');
