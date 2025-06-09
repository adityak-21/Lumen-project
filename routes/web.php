<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    // return $router->app->version();
    echo "Hi everyone!";
});

$router->get('/db-test', function() {
    return app('db')->select("SELECT 1 as Test");
});

$router->get('/version', function () use ($router) {
    return $router->app->version();
});

Route::group([

    'prefix' => 'api/v1',

], function ($router) {
    Route::post('login', 'AuthController@login');
    Route::post('register', 'AuthController@register');
    Route::post('refresh', 'AuthController@refresh');

});

Route::group([

    'prefix' => 'api/v1',
    'middleware' => 'auth:api'

], function ($router) {
    Route::post('logout', 'AuthController@logout');
    Route::post('user-profile', 'AuthController@me');
    Route::post('test', 'AuthController@test');

});
