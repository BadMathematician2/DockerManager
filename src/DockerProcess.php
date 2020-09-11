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
     * @var array
     */
    private $output;

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
     * Повертає хеадер або контейнери, причому є варіант всіх або тільки запущенних.
     * @param string $key
     * @return false|string[]
     */
    public function getOutput(string $key)
    {
        if (! $this->output) {
            $this->initOutput();
        }

        return$this->output[$key];
    }


    /**
     * @param $containerId
     * @return string
     */
    public function remove($containerId)
    {
        return $this->process(['docker', 'rm', $containerId]);
    }

    /**
     * @param $containerId
     * @return string
     */
    public function stop($containerId)
    {
        return $this->process(['docker', 'stop', $containerId]);
    }

    private function initOutput()
    {
        $result = explode(PHP_EOL, $this->process(['docker', 'ps']));
        $this->output['header_running']= array_shift($result);
        $this->output['running_containers'] = $result;

        $result = explode(PHP_EOL, $this->process(['docker', 'ps', '-a']));
        $this->output['header_all'] = array_shift($result);
        $this->output['all_containers'] = $result;
    }

}
