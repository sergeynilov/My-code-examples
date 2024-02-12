<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\ReportsController;
//use App\Http\Controllers\Reports\ReportsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::post('login', [AuthController::class, 'login'])->name('login');
Route::post('register', [AuthController::class, 'register'])->name('register');

//Route::post('register', 'Api\\AuthController@register');

/* Route::group(['prefix' => 'auth'], function () {
    Route::post('login','AuthController@login');
    Route::post('signup','AuthController@signup');

    Route::group(['middleware' => 'auth:api'], function () {
        Route::get('logout','AuthController@logout');
        Route::get('user','AuthController@user');
    });
}); */

Route::post('showTaskDetailsReport/{taskId}', [ReportsController::class, 'showTaskDetailsReport'])->name('reports.showTaskDetailsReport');


Route::middleware('auth:sanctum') ->group(function () {
    // Tasks
    Route::post('tasks/filter', [TaskController::class, 'filter'])->name('tasks.filter');
    Route::apiResource('tasks', TaskController::class);
    Route::group(['prefix' => 'tasks', 'as' => 'tasks.'], function () {
        Route::put('{task_id}/done', [TaskController::class, 'done'])->name('done');
        //        Route::put('{task_id}/deactivate', [TaskController::class, 'deactivate'])->name('deactivate');

    });
});
