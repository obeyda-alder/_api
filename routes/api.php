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

    Route::group(['prefix' => 'auth', 'namespace' => 'Auth'], function () {
        Route::post('login', 'AuthController@login')->name('cms::login');
        Route::post('refresh', 'AuthController@refresh');
        Route::post('register', 'AuthController@register');
        Route::post('logout', 'AuthController@logout');
    });

    Route::group(['middleware' => 'ofType:ROOT,ADMINS,EMPLOYEES'], function ($router) {
        Route::group(['prefix' => 'users/u/', 'namespace' => 'Users'], function(){
            Route::get('/edit/{id}', 'UsersController@UserData');
            Route::get('/all_users_data', 'UsersController@AllUsers');
            Route::post('create', 'UsersController@store');
            Route::post('update/{id}', 'UsersController@update');

            //
            Route::post('e/store', 'UsersController@update');
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

        Route::group(['prefix' => 'agencies', 'namespace' => 'Agencies'], function(){
            Route::get('a/{agencies_type}', 'AgenciesController@index')->name('agencies');
            Route::get('agencies_data', 'AgenciesController@data')->name('agencies::data');
            Route::get('create/{agencies_type}', 'AgenciesController@create')->name('agencies::create');
            Route::post('store/{agencies_type}', 'AgenciesController@store')->name('agencies::store');
            Route::get('edit/{id}/{agencies_type}', 'AgenciesController@show')->name('agencies::edit');
            Route::post('e/store', 'AgenciesController@update')->name('agencies::e-store');
            Route::post('soft_delete/{id}', 'AgenciesController@softDelete')->name('agencies::soft_delete');
            Route::post('delete/{id}', 'AgenciesController@delete')->name('agencies::delete');
            Route::post('restore/{id}', 'AgenciesController@restore')->name('agencies::restore');
        });

        Route::group(['prefix' => 'categories', 'namespace' => 'Categories'], function(){
            Route::get('', 'CategoriesController@index')->name('categories');
            Route::get('generateCode', 'CategoriesController@generateCode')->name('categories::generateCode');
            Route::get('categories_data', 'CategoriesController@data')->name('categories::data');
            Route::get('create', 'CategoriesController@create')->name('categories::create');
            Route::post('store', 'CategoriesController@store')->name('categories::store');
            Route::get('edit/{id}', 'CategoriesController@show')->name('categories::edit');
            Route::post('e/store', 'CategoriesController@update')->name('categories::e-store');
            Route::post('soft_delete/{id}', 'CategoriesController@softDelete')->name('categories::soft_delete');
            Route::post('delete/{id}', 'CategoriesController@delete')->name('categories::delete');
            Route::post('restore/{id}', 'CategoriesController@restore')->name('categories::restore');
        });

        Route::group(['prefix' => 'units', 'namespace' => 'Units'], function(){
            Route::get('', 'UnitsController@index')->name('units');
            Route::get('generateCode', 'UnitsController@generateCode')->name('units::generateCode');
            Route::get('getCategory', 'UnitsController@getCategory')->name('units::getCategory');
            Route::get('units_data', 'UnitsController@data')->name('units::data');
            Route::get('create', 'UnitsController@create')->name('units::create');
            Route::post('store', 'UnitsController@store')->name('units::store');
            Route::get('edit/{id}', 'UnitsController@show')->name('units::edit');
            Route::post('e/store', 'UnitsController@update')->name('units::e-store');
            Route::post('soft_delete/{id}', 'UnitsController@softDelete')->name('units::soft_delete');
            Route::post('delete/{id}', 'UnitsController@delete')->name('units::delete');
            Route::post('restore/{id}', 'UnitsController@restore')->name('units::restore');
        });
    });
});
