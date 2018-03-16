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

$router->get('/import', 'ImportController@show')->name('import.show');
$router->post('import', 'ImportController@store')->name('import.store');

Route::get('/', function () {
    return view('welcome');
});

// Route::get('/import', function () {
//     return 'import';
// });

