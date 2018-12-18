<?php

namespace Ajtarragona\AlfrescoLaravel;

use Illuminate\Support\ServiceProvider;

class AlfrescoLaravelServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->bootConfig();
    }

    /**
     * Defines the boot configuration
     *
     * @return void
     */
    protected function bootConfig()
    {
        $path = __DIR__.'/alfresco.php';
        $this->mergeConfigFrom($path, 'alfresco');
        if (function_exists('config_path')) {
            $this->publishes([$path => config_path('alfresco.php')],'alfresco');
        }
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->loadRoutesFrom(__DIR__.'/routes.php');
        $this->app->make('Ajtarragona\AlfrescoLaravel\Controllers\AlfrescoLaravelController');
        $this->app->make('Ajtarragona\AlfrescoLaravel\Models\AlfrescoLaravel');
    }
}
