<?php


namespace DockerManager;


use Symfony\Component\Process\Process;

/**
 * Class DockerProcess
 * @package DockerManager
 */
class DockerProcess
{
    /**
     * Створює і запускає процес.
     * @param array $command
     * @return string
     */
    public function process(array $command)
    {
        $process = new Process($command);
        $process->run();

        return $process->getOutput();
    }

    /**
     * @return false|string[]
     */
    public function dockerPs()
    {
        return explode("\n",$this->process(['docker', 'ps']));
    }

    /**
     * @return false|string[]
     */
    public function dockerPsA()
    {
        return explode("\n",$this->process(['docker', 'ps', '-a']));
    }

    /**
     * @param $containerId
     * @return string
     */
    public function dockerRm($containerId)
    {
        return $this->process(['docker', 'rm', $containerId]);
    }

    /**
     * @param $containerId
     * @return string
     */
    public function dockerStop($containerId)
    {
        return $this->process(['docker', 'stop', $containerId]);
    }

}
