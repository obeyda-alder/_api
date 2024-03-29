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
        Route::get('migrate',   function(){ \Artisan::call('migrate'); dd('done'); });
        Route::get('clearcach', function(){ \Artisan::call('cache:clear');  dd('done'); });
        Route::get('rollback',  function(){ \Artisan::call('migrate:rollback', ['--step' => 1]); dd('done'); });
    });

    Route::group(['prefix' => 'auth', 'namespace' => 'CmsApi\Auth'], function () {
        Route::post('login', 'AuthController@login');
        Route::post('refresh', 'AuthController@refresh');
        Route::post('register', 'AuthController@register');
        Route::post('logout', 'AuthController@logout');
    });

    Route::group(['middleware' => 'ofType:ROOT,ADMIN,EMPLOYEE'], function ($router) {

        Route::group(['prefix' => 'users/u/', 'namespace' => 'CmsApi\Users'], function(){
            Route::get('edit/{id}', 'UsersController@UserData');
            Route::get('all_users_data', 'UsersController@AllUsers');
            Route::post('create', 'UsersController@store');
            Route::post('update', 'UsersController@update');
            Route::post('soft_delete/{id}', 'UsersController@softDelete');
            Route::post('delete/{id}', 'UsersController@delete');
            Route::post('restore/{id}', 'UsersController@restore');
            Route::get('default/{file}', 'UsersController@default');


            Route::get('check_orders', 'UsersController@fetchOrders');
            Route::get('refresh_data', 'UsersController@refresh_data');
            Route::get('packing_order', 'UsersController@PackingOrder');
            Route::get('units_history', 'UsersController@UnitsHistory');
            Route::get('money_history', 'UsersController@MoneyHistory');
            Route::get('index_movement', 'UsersController@index_movement');
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


        Route::group(['prefix' => 'relation_unit_type_with_operations', 'namespace' => 'CmsApi\Categories'], function(){
            Route::get('', 'RelationUnitTypeWithOperationsController@index');
            Route::post('create', 'RelationUnitTypeWithOperationsController@create');
            Route::post('delete/{id}', 'RelationUnitTypeWithOperationsController@delete');
        });


        Route::group(['prefix' => 'relations_type', 'namespace' => 'CmsApi\Categories'], function(){
            Route::get('', 'RelationsTypeController@index');
            Route::post('create', 'RelationsTypeController@create');
            Route::post('delete/{id}', 'RelationsTypeController@delete');
        });

        Route::group(['prefix' => 'categories', 'namespace' => 'CmsApi\Categories'], function(){
            Route::get('', 'CategoriesController@index');
            Route::get('generate_code', 'CategoriesController@generateCode');
            Route::get('edit/{id}', 'CategoriesController@edit');
            Route::post('create', 'CategoriesController@create');
            Route::post('update/{id}', 'CategoriesController@update');
            Route::post('soft_delete/{id}', 'CategoriesController@softDelete');
            Route::post('delete/{id}', 'CategoriesController@delete');
            Route::post('restore/{id}', 'CategoriesController@restore');
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

        Route::group(['prefix' => 'money_operations', 'namespace' => 'CmsApi\Finance'], function(){
            Route::get('', 'FinanceController@index');
            Route::post('batch_creation', 'FinanceController@BatchCreation');
            Route::post('soft_delete/{id}', 'FinanceController@softDelete');
            Route::post('delete/{id}', 'FinanceController@delete');
            Route::post('restore/{id}', 'FinanceController@restore');
        });

        Route::group(['prefix' => 'config', 'namespace' => 'CmsApi'], function(){
            Route::get('', 'ConfigController@index');
            Route::post('create', 'ConfigController@create');
            Route::post('delete/{id}', 'ConfigController@delete');
        });

        Route::group(['prefix' => 'make_operations', 'namespace' => 'CmsApi\Operations'], function(){
            Route::post('{operation_type}', 'MakeOperationsController@operation');
        });
    });
});
