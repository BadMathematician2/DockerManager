<?php


namespace DockerManager;


use DockerManager\Exceptions\ContainerRunningException;
use Symfony\Component\Process\Process;

/**
 * Class DockerManager
 * @package DockerManager
 */
class DockerManager
{
    /**
     * Назви стовпчиків.
     * @var string[]
     */
    private $columns;

    /**
     * @var array
     */
    private $container;

    /**
     * Втсановлює назви ствопчиків, за умови, що між назвами має бути принаймі два пробіли.
     * Якщо у назві є пробіл, то замінює його на '_'.
     *
     * @param string $str
     */
    private function initColumns(string $str) {
        $columns = preg_replace('/\s{2,}/', '%', $str);
        $columns = str_replace(' ', '_', $columns);
        $this->columns = explode('%', $columns);
    }

    /**
     * Повертає масив із усіма контейнерами.
     * Те чи контейнер запущенний показує поле 'RUNNING': true - запущений, false - ні.
     *
     * @return array
     */
    public function getContainers()
    {
        $this->recordAll();
        $this->updateRunning();

        return $this->container;
    }

    /**
     * Записує всі контейнери в поле $dockers.
     * За замовчуванням вказується, що всі контейнери не запущені.
     */
    private function recordAll()
    {
        $containers = explode("\n",$this->process(['docker', 'ps', '-a']));
        $this->initColumns($containers[0]);

        for ($i = 1; $i < sizeof($containers) - 1; $i++) {
            $this->container[] = $this->getContainerInfo($containers[0], $containers[$i]);
        }
    }

    /**
     * Перевіряє, які контейнери запущенні і вказує це у 'RUNNING'.
     */
    private function updateRunning()
    {
        $containers = explode("\n",$this->process(['docker', 'ps']));
        $length = stripos($containers[0], $this->columns[1]);
        for ($i = 1; $i < sizeof($containers) - 1; $i++) {
            $this->setRunning($length, $containers[$i]);
        }
    }

    /**
     * Зупинає контейнер по його id.
     * @param string $containerId
     * @return string
     */
    public function stopContainer(string $containerId)
    {
        return $this->process(['docker', 'stop', $containerId]);
    }

    /**
     * Зупиняє всі запущенні контейнери.
     */
    public function stopAllContainers()
    {
        if (empty($this->container)) {
            $this->getContainers();
        }

        foreach ($this->container as $key => $value) {
            if ($value['RUNNING']) {
                $this->stopContainer($key);
            }
        }
    }

    /**
     * Видаляє контейнер по id, якщо він запущений то видає exception
     * @param string $containerId
     * @return string
     * @throws ContainerRunningException
     */
    public function deleteContainer(string $containerId)
    {
        if ('' === $this->process(['docker', 'rm', $containerId])) {
            throw new ContainerRunningException('Container is running');
        }

        return $containerId;
    }

    /**
     * Розбиває інформацію про контейнер на стовпики, яка міститься в $str.
     * $template - це рядок із назвами стовпчиків, базуючись на який ми будемо ділити інформацію в $str/
     * @param string $template
     * @param string $str
     * @return array
     */
    private function getContainerInfo(string $template, string $str)
    {
        $end = 0;
        $result = [];

        for ($i = 0; $i < sizeof($this->columns) - 1; $i++) {
            $begin = $end;
            $end = stripos($template, $this->columns[$i + 1]);
            $result[$this->columns[$i]] = $this->trimSubstr($str, $begin, $end);
        }

        $result[$this->columns[sizeof($this->columns) - 1]] = $this->trimSubstr($str, $end, strlen($str));

        $result['RUNNING'] = false;

        return $result;
    }

    /**
     * Встановлює поле 'RUNNING' = true (тобто що контейнер запущений) для контейнера,
     * id - це підстрока довжини $length в рядку $str.
     * @param int $length
     * @param string $str
     * @return null
     */
    private function setRunning(int $length, string $str)
    {
        foreach ($this->container as $key => $value) {
            if ($value[$this->columns[0]] === trim(substr($str, 0, $length))) {
                $this->container[$key]['RUNNING'] = true;
                return null;
            }
        }

        return null;
    }

    /**
     * Бере підстроку із $str починаючи із $begin і до $end, і видаляє всі пробіли.
     * @param string $str
     * @param int $begin
     * @param int $end
     * @return string
     */
    private function trimSubstr(string $str, int $begin, int $end)
    {
        return trim(substr($str, $begin, $end - $begin));
    }

    /**
     * Створює і запускає процес.
     * @param array $command
     * @return string
     */
    private function process(array $command)
    {
        $process = new Process($command);
        $process->run();

        return $process->getOutput();
    }
}
