<?php

namespace Programmer9WC\QueryBuilder\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

class QueryBuilderServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(Router $router)
    {
        
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');

        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'wc_querybuilder');

        require_once __DIR__ . '/../Helpers/helpers.php';

    }
    
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
    

}
