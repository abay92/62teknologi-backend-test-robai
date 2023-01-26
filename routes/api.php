<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'namespace' => '\App\Http\Controllers\Api',
    'middleware' => 'localization'
], function ($router) {
    $router->prefix('category')->group(function ($router) {
        $router->get('/', 'CategoryController@index');
    });

    $router->prefix('business')->group(function ($router) {
        $router->get('/search', 'BusinessController@index');
        $router->get('/{id}', 'BusinessController@show');
        $router->post('/', 'BusinessController@store');
        $router->put('/{id}', 'BusinessController@update');
        $router->delete('/{id}', 'BusinessController@destroy');
        $router->post('/rating', 'BusinessController@storeRating');
    });
});
