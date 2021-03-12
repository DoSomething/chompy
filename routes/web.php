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
$router->post('upload/{importType}', 'ImportFileController@upload')->name('import.upload');

Route::resource('import-files', 'ImportFileController', ['only' => ['index', 'show']]);

Route::resource('rock-the-vote-reports', 'RockTheVoteReportController', [
    'except' => ['delete'],
]);

Route::resource('exports', 'ExportController', [
    'only' => ['create', 'store'],
]);

Route::resource('users', 'UserController', ['only' => ['show']]);

Route::get('/', function () {
    return \Auth::check() ? redirect('import-files') : view('pages.home');
});

// Authentication
$router->get('login', 'Auth\LoginController@getLogin')->name('login');
$router->get('logout', 'Auth\LoginController@getLogout')->name('logout');
