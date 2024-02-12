<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Admin\TodoController;
use App\Http\Controllers\Admin\TodoTaskController as AdminTodoTaskController;
use App\Http\Controllers\Manager\TodoController as ManagerTodoController;
use App\Http\Controllers\Manager\TodoTaskController as ManagerTodoTaskController;
//use App\Http\Controllers\Admin\AdminDashboardController;

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
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});


Route::prefix('admin')->name('admin.')->middleware(['auth:sanctum', 'verified', 'CheckIsAdmin' ])->group(function() {

    Route::resource('todos', TodoController::class)->except(['show', 'create', 'store']);
    Route::post('todos/filter', [TodoController::class, 'filter'])->name('todos.filter');

    Route::get('todo_tasks/{todo_id}', [AdminTodoTaskController::class, 'index'])->name('todo_tasks.index');
    Route::get('todo_tasks/{todo_id}/{todo_task_id}', [AdminTodoTaskController::class, 'details'])->name('todo_tasks.details');
    Route::delete('todo_tasks/{todo_task_id}', [AdminTodoTaskController::class, 'destroy'])->name('todo_tasks.destroy');

    Route::put('todo_tasks/{todo_id}', [AdminTodoTaskController::class, 'update'])->name('todo_tasks.update');
    Route::post('todo_tasks/{todo_id}', [AdminTodoTaskController::class, 'store'])->name('todo_tasks.store');

});

Route::prefix('manager')->name('manager.')->middleware(['auth:sanctum', 'verified', 'CheckIsManager' ])->group(function() {
    Route::get('todos', [ManagerTodoController::class,'index']);
    Route::post('todos/filter', [ManagerTodoController::class, 'filter'])->name('todos.filter');

    Route::put('todo_task/disable/{todo_id}/{todo_task_id}' , [ManagerTodoTaskController::class, 'disable'])->name('todo_task.disable');
    Route::put('todo_task/uncomplete/{todo_id}/{todo_task_id}' , [ManagerTodoTaskController::class, 'uncomplete'])->name('todo_task.uncomplete');
    Route::put('todo_task/complete/{todo_id}/{todo_task_id}' , [ManagerTodoTaskController::class, 'complete'])->name('todo_task.complete');

});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');
});
