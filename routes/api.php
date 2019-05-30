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
$router->group(['prefix' => 'v1'], function () {
    // CallPower
    $this->post('callpower/call', 'ThirdParty\CallPowerController@store');
    // SoftEdge
    $this->post('softedge/email', 'ThirdParty\SoftEdgeController@store');
});
