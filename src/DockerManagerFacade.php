<?php


namespace DockerManager;


use Illuminate\Support\Facades\Facade;

class DockerManagerFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'DockerManager';
    }
}
