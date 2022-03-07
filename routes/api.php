<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api;

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

Route::get('/', function () {
    return 'Welcome to Casamania APIs 😇 ...';
});

Route::prefix('user')->group(function () {
    Route::post('/register', [Api\UserController::class, 'register']);
    Route::post('/login', [Api\UserController::class, 'login']);

    Route::post('authorize', [
        'as' => 'auth.token',
        'uses' => '\Laravel\Passport\Http\Controllers\AccessTokenController@issueToken'
    ]);
});

Route::group(['middleware' => ['auth:api'], 'prefix' => 'property'], function () {
    Route::get('/', function () {
        return "Get Property...";
    });
    Route::get('/mls_id/{mls_id}', [Api\PropertyController::class, 'getDetails']);
    Route::get('/get-featured-listings-mls', [Api\PropertyController::class, 'getDetailsofMultipleMLS_ID']);
    Route::post('/filter', [Api\PropertyController::class, 'filter']);
    Route::get('/property_type/{type}', [Api\PropertyController::class, 'type']);
});
