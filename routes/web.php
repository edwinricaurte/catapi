<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/','App\Http\Controllers\CatAPIController@home');
Route::get('createCatAPIVoting','App\Http\Controllers\CatAPIController@createCatAPIVoting');

Route::post('vote','App\Http\Controllers\FrontEndController@vote');
Route::post('my-votes','App\Http\Controllers\FrontEndController@getVotesByUserId');
Route::post('votes-summary','App\Http\Controllers\FrontEndController@getVotesSummary');
Route::post('reset-votes','App\Http\Controllers\FrontEndController@resetVotes');

