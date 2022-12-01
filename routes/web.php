<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DummyLoginController;
use Illuminate\Support\Facades\Route;

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

Route::get('/', function () {
    return view('welcome');
});
Route::get('dummy/login', [DummyLoginController::class, 'index']);
Route::post('dummy/login', [DummyLoginController::class, 'checkValid']);
Route::post('dummy/checkvalidid', [DummyLoginController::class, 'checkValidId']);
Route::post('dummy/checkvaliddata', [CompanyController::class, 'checkValidData']);
