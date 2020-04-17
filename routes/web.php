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

Route::resource('import-files', 'ImportFileController', ['only' => ['index', 'show']]);

Route::resource('rock-the-vote-reports', 'RockTheVoteReportController', [
    'except' => ['delete', 'update'],
]);

$router->get('tests/{importType}', 'TestController@create')->name('test.show');
$router->post('tests/{importType}', 'TestController@submit')->name('test.submit');

Route::resource('users', 'UserController', ['only' => ['show']]);

Route::get('/', function () {
    return \Auth::check() ? redirect('import-files') : view('pages.home');
});

// Authentication
$router->get('login', 'Auth\LoginController@getLogin')->name('login');
$router->get('logout', 'Auth\LoginController@getLogout')->name('logout');
