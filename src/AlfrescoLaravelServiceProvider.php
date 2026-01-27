<?php

namespace Ajtarragona\AlfrescoLaravel;

use Illuminate\Support\ServiceProvider;
use Illuminate\Filesystem\FilesystemAdapter as LaravelFilesystemAdapter;
use League\Flysystem\Filesystem as Flysystem;
use Illuminate\Support\Facades\Storage;
use Ajtarragona\AlfrescoLaravel\Models\AlfrescoService;
use Ajtarragona\AlfrescoLaravel\AlfrescoAdapter;

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

        $this->registerDiskAdapter();


    }

    protected function registerDiskAdapter()
    {

        Storage::extend('alfresco', function ($app, $config) {
            $service = new AlfrescoService($config);
            $adapter = new AlfrescoAdapter($service);

            return new LaravelFilesystemAdapter(
                new Flysystem($adapter, $config),
                $adapter,
                $config
            );
        });
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
        
        //registro middleware
        $this->app['router']->aliasMiddleware('alfresco-explorer', \Ajtarragona\AlfrescoLaravel\Middlewares\AlfrescoExplorer::class);

        $this->app->bind('alfresco', function(){
            return new \Ajtarragona\AlfrescoLaravel\Models\AlfrescoService;
        });


        //helpers
        foreach (glob(__DIR__.'/Helpers/*.php') as $filename){
            require_once($filename);
        }
    }
}

