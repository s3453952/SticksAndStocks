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
use App\Stock;

Route::get('/', function () {
	return view('home');
});
Route::get('template', function () {
    return view('template');
});

Route::get('dashboard', function () {
	return view('dashboard');
})->middleware('auth');

Route::get('stock/{id}', 'ShowStock');

Auth::routes();

Route::get('logout', function () {
	Auth::logout();
	return redirect('/');
});

Route::post('createAccount', 'TradeAccountController@create');
Route::post('editUser', 'UserAccountController@edit');
