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
     * @var string
     */
    private $header;

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
     * @param false $all
     * @return false|string[]
     */
    public function getContainerListing($all = false)
    {
        $command = ['docker', 'ps'];
        if ($all) {
            $command[] = '-a';
        }

        $result = explode(PHP_EOL, $this->process($command));
        $this->header = $this->header ?? array_shift($result);

        return $result;
    }

    /**
     * @return string
     */
    public function getHeader()
    {
        if (! $this->header) {
            $this->getContainerListing();
        }

        return $this->header;
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

}
