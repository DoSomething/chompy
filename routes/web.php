<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::resource('failed-jobs', 'FailedJobController');

$router->get('import/{importType}', 'ImportFileController@create')->name('import.show');
$router->post('import/{importType}', 'ImportFileController@store')->name('import.store');

Route::resource('import-files', 'ImportFileController', ['only' => 'index']);

Route::get('/', function () {
    return view('pages.home');
});

// Authentication
$router->get('login', 'Auth\LoginController@getLogin')->name('login');
$router->get('logout', 'Auth\LoginController@getLogout')->name('logout');
