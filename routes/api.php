<?php

use Illuminate\Http\Request;

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// Third Party Integration
Route::group(['prefix' => 'v1'], function () {
    // CallPower
    Route::post('callpower/call', 'ThirdParty\CallPowerController@store');

    // SoftEdge
    Route::post('softedge/email', 'ThirdParty\SoftEdgeController@store');
});
