<?php

use Illuminate\Support\Facades\Route;
use Programmer9WC\QueryBuilder\Http\Controllers\QueryBuilderController;
use Programmer9WC\QueryBuilder\Http\Controllers\QueryReportsController;


/**
 * Group routes under the 'web' middleware for session management, CSRF protection, etc.
 */
// Route::middleware(['web','auth'])->group(function () {
Route::middleware(['web'])->group(function () {


    /**
     * Query Builder Routes
     * 
     * Defines routes related to building database queries.
     * Uses a route prefix of 'query-builder' to group related endpoints.
     */
    Route::controller(QueryBuilderController::class)->prefix('query-builder')->group(function () {

        Route::get( '/',                        'index')            ->name('query-builder.index');
        Route::get( '/columns/{table}',         'getColumns')       ->name('query-builder.columns');
        Route::get( '/relations/{table}',       'getRelations')     ->name('query-builder.relations');
        Route::post('/search',                  'search')           ->name('query-builder.search');
        Route::get('/search',                   'search')           ->name('query-builder.search');
        Route::post('/save',                    'save')             ->name('query-builder.save');

    });


    /**
     * Query Reports Routes
     * 
     * Defines routes related to saving, editing, and viewing query reports.
     * Uses a route prefix of 'query-report' for better organization.
     */
    Route::controller(QueryReportsController::class)->prefix('query-report')->group(function () {

        Route::get( '/',                        'index')            ->name('query-report.index');
        Route::get( '/edit/{id}',               'edit')             ->name('query-report.edit');
        Route::get( '/view/{id}',               'view')             ->name('query-report.view');
        Route::post( '/delete',                 'delete')           ->name('query-report.delete');

    });

});
