<?php


namespace DockerManager;


use Illuminate\Support\ServiceProvider;

class DockerManagerProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('DockerManager', function () {
            return new DockerManager();
        });
    }
}
