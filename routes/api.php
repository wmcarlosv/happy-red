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

//Route::middleware('auth:api')->get('/user', function (Request $request) {
//    return $request->user();
//});

Route::group([
    'prefix' => 'auth'
], function () {

    Route::post('login', 'Auth\AuthController@login')->name('login');
    Route::post('register', 'Auth\AuthController@register');
    Route::put('recovery-password', 'Auth\AuthController@recovery_password');
    Route::post('validate-code', 'Auth\AuthController@validate_code');
    Route::put('change-password', 'Auth\AuthController@change_password');

    Route::group([
        'middleware' => 'auth:api'
    ], function () {
        Route::get('logout', 'Auth\AuthController@logout');
        Route::get('user', 'Auth\AuthController@user');
        Route::get('user/list', 'Auth\AuthController@userList');
    });


});

Route::group([
    'middleware' => 'auth:api'
], function () {
    Route::get('user/list', 'Auth\AuthController@userList');
    Route::get('user/users-invited', 'Auth\AuthController@users_invited');
    Route::get('user/send-message/{receiver_id}/{message}', 'Auth\AuthController@send_message');
    Route::get('user/get-notifications', 'Auth\AuthController@get_notifications');
    Route::get('user/list-notifications', 'Auth\AuthController@list_notifications');
    Route::get('game/user', 'GameController@gameUser');
    Route::get('game/pay-first-user/{id}', 'GameController@firstGame');
    Route::post('game/pay', 'GameController@gamePay');
    Route::get('game/data', 'GameController@gameData');
    Route::post('game/data/level', 'GameController@gameDataLevel');
    Route::post('game/start', 'GameController@gameStart');
    Route::get('game/tree', 'GameController@gameDataTree');
    Route::get('game/game-by-level/{level}', 'GameController@getDataByLevel');
    Route::get('game/validate-end-game', 'GameController@validateEndGame');
    Route::get('notification/get', 'NotificationController@getMessages');
    Route::post('notification/sent', 'NotificationController@sentMessage');
    Route::post('notification/read', 'NotificationController@readMessage');
});