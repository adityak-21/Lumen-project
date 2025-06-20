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

$router->get('/api/confirm/{token}', 'AuthController@confirmEmail');

Route::group([

    'prefix' => 'api/v1',

], function ($router) {
    Route::post('login', 'AuthController@login');
    Route::post('register', 'AuthController@register');
    Route::post('refresh', 'AuthController@refresh');
    Route::post('resetpwd/{token}', 'AuthController@resetPassword');
    Route::post('forgotpwd', 'AuthController@forgotPassword');

});

Route::group([

    'prefix' => 'api/v1',
    'middleware' => 'auth:api'

], function ($router) {
    Route::post('logout', 'AuthController@logout');
    Route::post('user-profile', 'AuthController@me');
    Route::post('test', 'AuthController@test');
    Route::post('softdelete/{id}', 'UserController@softDeleteUser');
    Route::post('bulkdelete', 'UserController@bulksoftDeleteUsers');
    Route::post('update-name/{userId}', 'UserController@updateName');
    Route::post('listUsers', 'UserController@listUser');
    Route::post('listActivities', 'UserController@listUserActivity');
    Route::post('validate', 'AuthController@isValidUser');
    Route::post('isAdmin', 'AuthController@isAdmin');

});

Route::group([
    'prefix' => 'api/roles'

], function ($router) {
    Route::get('/', 'RoleController@index');
    Route::post('create', 'RoleController@create');
    Route::get('{id}', 'RoleController@show');
    Route::delete('{id}', 'RoleController@destroy');
});

$router->post('api/users/{userId}/roles', 'UserRoleController@assignRoles');
$router->delete('api/users/{userId}/roles/{roleId}', 'UserRoleController@removeRole');