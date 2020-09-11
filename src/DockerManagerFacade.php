<?php


namespace DockerManager;


use Illuminate\Support\Facades\Facade;

/**
 * Class DockerManagerFacade
 * @package DockerManager
 */
class DockerManagerFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'DockerManager';
    }
}
