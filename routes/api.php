<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::group(['middleware' => 'api', 'prefix' => 'cms'], function ($router) {

    Route::group(['middleware' => ['jwt.auth', 'ofType:ROOT'], 'prefix' => 'setting'], function () {
        Route::get('migrate', function(){     \Artisan::call('migrate'); dd('done'); });
        Route::get('clearcach', function(){   \Artisan::call('cache:clear');  dd('done'); });
        Route::get('rollback', function(){    \Artisan::call('migrate:rollback', ['--step' => 1]); dd('done'); });
    });

    Route::group(['prefix' => 'auth', 'namespace' => 'CmsApi\Auth'], function () {
        Route::post('login', 'AuthController@login')->name('cms::login');
        Route::post('refresh', 'AuthController@refresh');
        Route::post('register', 'AuthController@register');
        Route::post('logout', 'AuthController@logout');
    });

    Route::group(['middleware' => 'ofType:ROOT,ADMINS,EMPLOYEES,AGENCIES'], function ($router) {

        Route::group(['prefix' => 'users/u/', 'namespace' => 'CmsApi\Users'], function(){
            Route::get('edit/{id}', 'UsersController@UserData');
            Route::get('all_users_data', 'UsersController@AllUsers');
            Route::post('create', 'UsersController@store');
            Route::post('update/{id}', 'UsersController@update');
            Route::post('soft_delete/{id}', 'UsersController@softDelete');
            Route::post('delete/{id}', 'UsersController@delete');
            Route::post('restore/{id}', 'UsersController@restore');
        });

        Route::group(['prefix' => 'addresses', 'namespace' => 'Addresses'], function(){
            Route::get('get_Countries', 'AddressController@getCountries');
            Route::get('get_Cities', 'AddressController@getCities');
            Route::get('get_Municipalites', 'AddressController@getMunicipalites');
            Route::get('get_Neighborhoodes', 'AddressController@getNeighborhoodes');
        });


        Route::group(['prefix' => 'operations', 'namespace' => 'CmsApi\Categories'], function(){
            Route::get('', 'OperationsController@index');
            Route::post('create', 'OperationsController@create');
            Route::post('delete/{id}', 'OperationsController@delete');
        });


        Route::group(['prefix' => 'operation_type', 'namespace' => 'CmsApi\Categories'], function(){
            Route::get('', 'OperationTypeController@index');
            Route::post('create', 'OperationTypeController@create');
            Route::post('delete/{id}', 'OperationTypeController@delete');
        });


        Route::group(['prefix' => 'relations_type', 'namespace' => 'CmsApi\Categories'], function(){
            Route::get('', 'RelationsTypeController@index');
            Route::post('create', 'RelationsTypeController@create');
            Route::post('delete/{id}', 'RelationsTypeController@delete');
        });

        Route::group(['prefix' => 'categories', 'namespace' => 'CmsApi\Categories'], function(){
            Route::get('', 'CategoriesController@index');
            Route::get('generate_code', 'CategoriesController@generateCode');
            Route::post('create', 'CategoriesController@create');
            Route::post('update/{id}', 'CategoriesController@update');
            Route::post('soft_delete/{id}', 'CategoriesController@softDelete');
            Route::post('delete/{id}', 'CategoriesController@delete');
            Route::post('restore/{id}', 'CategoriesController@restore');
        });

        Route::group(['prefix' => 'agencies', 'namespace' => 'CmsApi\Agencies'], function(){
            Route::get('/{type}', 'AgenciesController@index');
            Route::post('create/{type}', 'AgenciesController@create');
            Route::post('update/{id}/{type}', 'AgenciesController@update');
            Route::post('soft_delete/{id}/{type}', 'AgenciesController@softDelete');
            Route::post('delete/{id}/{type}', 'AgenciesController@delete');
            Route::post('restore/{id}/{type}', 'AgenciesController@restore');
        });

        Route::group(['prefix' => 'transaction_type', 'namespace' => 'CmsApi\Units'], function(){
            Route::get('', 'TransactionTypeController@index');
            Route::post('create', 'TransactionTypeController@create');
            Route::post('delete/{id}', 'TransactionTypeController@delete');
        });

        Route::group(['prefix' => 'unit_type', 'namespace' => 'CmsApi\Units'], function(){
            Route::get('', 'UnitTypeController@index');
            Route::post('create', 'UnitTypeController@create');
            Route::post('delete/{id}', 'UnitTypeController@delete');
        });

        Route::group(['prefix' => 'units', 'namespace' => 'CmsApi\Units'], function(){
            Route::get('', 'UnitsController@index');
            Route::post('generate', 'UnitsController@generate');
            Route::post('soft_delete/{id}', 'UnitsController@softDelete');
            Route::post('delete/{id}', 'UnitsController@delete');
            Route::post('restore/{id}', 'UnitsController@restore');
        });

        Route::group(['prefix' => 'processes', 'namespace' => 'CmsApi\Processes'], function(){
            Route::post('{process_type}', 'ProcessesController@makeProcesses');
        });

    });
});
