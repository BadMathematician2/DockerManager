<?php


namespace DockerManager;


use Symfony\Component\Process\Process;

/**
 * Class DockerProcess
 * @package DockerManager
 */
class DockerProcess
{

    private $out;

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
     * Повертає контейнери.
     * Всі, якщо параметр true, в противному випадку тільки запущенні.
     * Також записує header.
     * @param string $key
     * @return false|string[]
     */
    public function getResult(string $key)
    {
        if (! $this->out) {
            $this->initOut();
        }

        return$this->out[$key];
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

    private function initOut()
    {
        $result = explode(PHP_EOL, $this->process(['docker', 'ps']));
        $this->out['header_running']= array_shift($result);
        $this->out['running_containers'] = $result;

        $result = explode(PHP_EOL, $this->process(['docker', 'ps', '-a']));
        $this->out['header_all'] = array_shift($result);
        $this->out['all_containers'] = $result;
    }

}
