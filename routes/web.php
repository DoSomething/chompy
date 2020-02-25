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

$router->get('import/{importType}', 'ImportController@show')->name('import.show');
$router->post('import/{importType}', 'ImportController@store')->name('import.store');

Route::resource('failed-jobs', 'FailedJobController');

Route::resource('import-files', 'ImportFileController');

Route::get('/', function () {
    return view('pages.home');
});

// Authentication
$router->get('login', 'Auth\LoginController@getLogin')->name('login');
$router->get('logout', 'Auth\LoginController@getLogout')->name('logout');
