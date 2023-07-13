<?php
use Illuminate\Support\Facades\Route;
use App\Modules\BusinessTrip\Controllers;

Route::middleware('check_auth')->prefix('business-trip')
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
            Route::post('list-action',Controllers\TaskController::class.'@listNeedAction');
            Route::post('edit',Controllers\TaskController::class.'@edit');
            Route::post('delete',Controllers\TaskController::class.'@delete');
        });

        Route::prefix('cities')->group(function (){
            Route::post('list',Controllers\CityController::class.'@list');
            Route::post('get',Controllers\CityController::class.'@get');
            Route::post('add',Controllers\CityController::class.'@add');
            Route::post('edit',Controllers\CityController::class.'@edit');
            Route::post('active',Controllers\CityController::class.'@active');
        });

        Route::prefix('targets')->group(function (){
            Route::post('list',Controllers\TargetController::class.'@list');
            Route::post('get',Controllers\TargetController::class.'@get');
            Route::post('add',Controllers\TargetController::class.'@add');
            Route::post('edit',Controllers\TargetController::class.'@edit');
            Route::post('active',Controllers\TargetController::class.'@active');
        });

        Route::prefix('cost-units')->group(function (){
            Route::post('list',Controllers\CostUnitController::class.'@list');
            Route::post('get',Controllers\CostUnitController::class.'@get');
            Route::post('add',Controllers\CostUnitController::class.'@add');
            Route::post('edit',Controllers\CostUnitController::class.'@edit');
            Route::post('delete',Controllers\CostUnitController::class.'@delete');
            Route::post('estimate-edit',Controllers\CostUnitController::class.'@estimateEdit');
        });

        Route::prefix('users')->group(function (){
            Route::post('get',Controllers\UserController::class.'@get');
            Route::post('list',Controllers\UserController::class.'@list');
            Route::post('add',Controllers\UserController::class.'@add');
            Route::post('delete',Controllers\UserController::class.'@delete');
        });

        Route::prefix('search')->group(function (){
            Route::post('city',Controllers\SearchController::class.'@city')->withoutMiddleware('check_auth');
            Route::post('target',Controllers\SearchController::class.'@target')->withoutMiddleware('check_auth');
            Route::post('user',Controllers\SearchController::class.'@user')->withoutMiddleware('check_auth');
            Route::post('company',Controllers\SearchController::class.'@company')->withoutMiddleware('check_auth');
            Route::post('department',Controllers\SearchController::class.'@department')->withoutMiddleware('check_auth');
        });

        Route::prefix('logs')->group(function (){
            Route::post('get',Controllers\LogController::class.'@get');
        });

        Route::prefix('document')->group(function (){
            Route::post('get',Controllers\DocumentController::class.'@get');
            Route::post('delete',Controllers\DocumentController::class.'@delete');
        });

        Route::post('change-status',Controllers\TaskController::class.'@changeStatus');

        Route::prefix('files')->group(function(){
            Route::post('update',Controllers\FilesController::class.'@update');
            Route::post('load',Controllers\FilesController::class.'@load');
            Route::post('get',Controllers\FilesController::class.'@get');
        });

        Route::prefix('work-follow')->group(function (){
            Route::post('set',Controllers\WorkFollow::class.'@set');
            Route::post('get',Controllers\WorkFollow::class.'@get');
        });

        Route::prefix('role')->group(function (){
            Route::post('add',Controllers\RoleController::class.'@add');
            Route::post('list',Controllers\RoleController::class.'@list');
            Route::post('delete',Controllers\RoleController::class.'@delete');
            Route::post('get',Controllers\RoleController::class.'@get');

            Route::prefix('additional')
                ->group(function(){
                    Route::post('set',  Controllers\AdditionalRightsController::class.'@setAdditionalRights');
                    Route::post('list', Controllers\AdditionalRightsController::class.'@listAdditionalRights');
                });
        });

        Route::prefix('actions')->group(function(){
            Route::post('update',Controllers\NeedActionController::class.'@update');
            Route::post('badge',Controllers\NeedActionController::class.'@badge');
        });



    });

