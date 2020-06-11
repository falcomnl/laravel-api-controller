<?php

namespace Falcomnl\LaravelApiController;

use Illuminate\Support\ServiceProvider;

class LaravelApiControllerServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}
