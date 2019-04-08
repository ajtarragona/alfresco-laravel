<?php

namespace Ajtarragona\AlfrescoLaravel;

use Illuminate\Support\ServiceProvider;
use Ajtarragona\AlfrescoLaravel\Models\AlfrescoProvider;

class AlfrescoLaravelServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //vistas
        $this->loadViewsFrom(__DIR__.'/resources/views', 'alfresco');
        
        $this->loadRoutesFrom(__DIR__.'/routes.php');
        
        $this->bootConfig();


    }

    /**
     * Defines the boot configuration
     *
     * @return void
     */
    protected function bootConfig()
    {   
        $path = __DIR__.'/Config/alfresco.php';
       
        $this->mergeConfigFrom($path, 'alfresco');
        
        $this->publishes([
            $path => config_path('alfresco.php')
        ],'ajtarragona-alfresco');
        
    }


    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //$this->app->make('Ajtarragona\AlfrescoLaravel\Controllers\AlfrescoLaravelController');
        //$this->app->make('Ajtarragona\AlfrescoLaravel\Models\AlfrescoLaravel');

        $this->app->bind('alfresco', function(){
            return new \Ajtarragona\AlfrescoLaravel\Models\AlfrescoProvider;
        });


        //helpers
        foreach (glob(__DIR__.'/Helpers/*.php') as $filename){
            require_once($filename);
        }
    }
}
