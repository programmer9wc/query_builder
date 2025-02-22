<?php

use Illuminate\Support\Facades\Route;

use Programmer9WC\QueryBuilder\Http\Controllers\QueryBuilderController;
use Programmer9WC\QueryBuilder\Http\Controllers\QueryReportsController;

Route::get('/', function () {
    return redirect()->route( 'dashboard' );
});

Route::middleware('web')->group(function () {

    Route::get('/dashboard', function () {
        return view('wc_querybuilder::index');
    })->name('dashboard');


    Route::controller(QueryBuilderController::class)->prefix('query-builder')->group(function () {

        Route::get( '/',                        'index')            ->name('query-builder.index');
        Route::get( '/columns/{table}',         'getColumns')       ->name('query-builder.columns');
        Route::get( '/relations/{table}',       'getRelations')     ->name('query-builder.relations');
        Route::post('/search',                  'search')           ->name('query-builder.search');
        Route::get('/search',                   'search')           ->name('query-builder.search');
        Route::post('/save',                    'save')             ->name('query-builder.save');

    });

    Route::controller(QueryReportsController::class)->prefix('query-report')->group(function () {

        Route::get( '/',                        'index')            ->name('query-report.index');
        Route::get( '/edit/{id}',               'edit')             ->name('query-report.edit');
        Route::get( '/view/{id}',               'view')             ->name('query-report.view');
        Route::post( '/delete',                 'delete')           ->name('query-report.delete');

    });

});
