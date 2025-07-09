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

$router->get('/version', function () use ($router) {
    return $router->app->version();
});


Route::group([

    'prefix' => 'api/v1',

], function ($router) {
    Route::post('login', 'AuthController@login');
    Route::post('register', 'AuthController@register');
    Route::post('refresh', 'AuthController@refresh');
    Route::post('resetpwd/{token}', 'AuthController@resetPassword');
    Route::post('forgotpwd', 'AuthController@forgotPassword');
    Route::post('confirm-email/{token}', 'AuthController@confirmEmail');

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

    Route::post('createTask', 'TaskController@createTask');
    Route::post('updateTaskTitle/{taskId}', 'TaskController@updateTaskTitle');
    Route::post('updateTaskDescription/{taskId}', 'TaskController@updateTaskDescription');
    Route::post('updateTaskDueDate/{taskId}', 'TaskController@updateTaskDueDate');
    Route::post('updateTaskStatus/{taskId}', 'TaskController@updateTaskStatus');
    Route::post('deleteTask/{taskId}', 'TaskController@deleteTask');
    Route::post('listMyTasks', 'TaskController@listMyTasks');
    Route::post('listCreatedTasks', 'TaskController@listCreatedTasks');
    Route::post('listAllTasks', 'TaskController@listAllTasks');
    Route::post('getTodayTasks', 'TaskController@getTodayTasks');

    Route::post('myTaskStatusStatistics', 'AnalyticsController@myTaskStatusStatistics');
    Route::post('averageCompletionTime', 'AnalyticsController@averageCompletionTime');
    Route::post('assignedVsCreated', 'AnalyticsController@assignedVsCreated');
    Route::post('oldestOpenTasks', 'AnalyticsController@oldestOpenTasks');

    Route::post('sendMessage', 'NotificationsController@sendMessage');
    Route::post('sendToUser', 'NotificationsController@sendToUser');

    Route::post('users/{userId}/roles', 'UserRoleController@assignRoles');
    Route::post('users/{userId}/roles/{roleId}', 'UserRoleController@removeRole');
    Route::post('users/{userId}/changeRoles', 'UserRoleController@changeRoles');

    Route::post('roles', 'RoleController@index');

    Route::post('listNotifications', 'NotificationsController@listNotifications');
    Route::post('markAsRead/{notificationId}', 'NotificationsController@markAsRead');
    Route::post('getRecent', 'NotificationsController@getRecent');
    Route::post('getRecentActivities', 'UserController@getRecentActivities');

});

Route::group([
    'prefix' => 'api/roles'

], function ($router) {
    Route::get('/', 'RoleController@index');
    Route::post('create', 'RoleController@create');
    Route::get('{id}', 'RoleController@show');
    Route::delete('{id}', 'RoleController@destroy');
});


$router->post('/broadcasting/auth', [
    'middleware' => 'auth:api',
    'uses' => 'BroadcastAuthController@authenticate'
]);
