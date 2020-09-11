<?php


namespace DockerManager;


use Illuminate\Support\ServiceProvider;

/**
 * Class DockerManagerProvider
 * @package DockerManager
 */
class DockerManagerProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('DockerManager', function () {
            return $this->app->make(DockerManager::class);
        });
    }
}
