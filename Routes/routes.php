<?php
use Illuminate\Support\Facades\Route;
use App\Modules\BusinessTrip\Controllers;

Route::prefix('business-trip')
    ->group(function(){

        Route::prefix('transfer')->group(function (){
            Route::get('cities',Controllers\TransferController::class.'@cities');
            Route::get('targets',Controllers\TransferController::class.'@targets');
            Route::get('cost-units',Controllers\TransferController::class.'@costUnits');
        });

        Route::prefix('tasks')->group(function (){
            Route::post('create',Controllers\TaskController::class.'@create');
            Route::post('get',Controllers\TaskController::class.'@get');
            Route::post('detail',Controllers\TaskController::class.'@detail');
            Route::post('list',Controllers\TaskController::class.'@list');
            Route::post('edit',Controllers\TaskController::class.'@edit');
            Route::post('delete',Controllers\TaskController::class.'@delete');
        });

        Route::prefix('cities')->group(function (){
            Route::post('list',Controllers\CityController::class.'@list');
            Route::post('add',Controllers\CityController::class.'@add');
            Route::post('active',Controllers\CityController::class.'@active');
        });

        Route::prefix('targets')->group(function (){
            Route::post('list',Controllers\TargetController::class.'@list');
            Route::post('add',Controllers\TargetController::class.'@add');
            Route::post('active',Controllers\TargetController::class.'@active');
            Route::post('get',Controllers\TargetController::class.'@get');
        });

        Route::prefix('cost-units')->group(function (){
            Route::post('list',Controllers\CostUnitController::class.'@list');
            Route::post('edit',Controllers\CostUnitController::class.'@edit');
        });

        Route::prefix('users')->group(function (){
            Route::post('get',Controllers\UserController::class.'@get');
            Route::post('list',Controllers\UserController::class.'@list');
            Route::post('add',Controllers\UserController::class.'@add');
            Route::post('delete',Controllers\UserController::class.'@delete');
        });

        Route::prefix('search')->group(function (){
            Route::post('city',Controllers\SearchController::class.'@city');
            Route::post('target',Controllers\SearchController::class.'@target');
            Route::post('user',Controllers\SearchController::class.'@user');
            Route::post('company',Controllers\SearchController::class.'@company');
            Route::post('department',Controllers\SearchController::class.'@department');
        });

        Route::prefix('logs')->group(function (){
            Route::post('get',Controllers\LogController::class.'@get');
        });

        Route::post('change-status',Controllers\TaskController::class.'@changeStatus');

        Route::prefix('files')->group(function(){
            Route::post('/add',Controllers\FilesController::class.'@add');
            Route::post('/edit',Controllers\FilesController::class.'@edit');
            Route::post('/load',Controllers\FilesController::class.'@load');
        });

    });

