<?php

namespace Tushar\LazyRouting;

use Illuminate\Support\ServiceProvider;

class LazyServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');
        $this->mergeConfigFrom(
            __DIR__.'/config/lazy_config.php', 'lazy_config'
        );
        $this->publishes([
            __DIR__.'/config/lazy_config.php' => config_path('lazy_config.php'),
        ]);
    }

    public function register()
    {
        
    }
}