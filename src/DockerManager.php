<?php


namespace DockerManager;


use DockerManager\Exceptions\ContainerRunningException;
use Throwable;

/**
 * Class DockerManager
 * @package DockerManager
 */
class DockerManager
{
    /**
     * @var string[]
     */
    private $columns;

    /**
     * @var array
     */
    private $containers;

    /**
     * @var DockerProcess
     */
    private $dockerProcess;


    /**
     * DockerManager constructor.
     * @param DockerProcess $dockerProcess
     */
    public function __construct(DockerProcess $dockerProcess)
    {
        $this->dockerProcess = $dockerProcess;
    }

    /**
     * Повертає масив із усіма контейнерами.
     * @return array
     */
    public function getContainers()
    {
        if (! $this->containers) {
            $this->recordAll();
            $this->updateRunning();
        }

        return $this->containers;
    }

    /**
     * Зупинає контейнер по його id.
     * @param string $containerId
     * @return string
     */
    public function stopContainer(string $containerId)
    {
        return $this->dockerProcess->stop($containerId);
    }

    /**
     * Зупиняє контейнери, id яких у масиві $keys.
     * Якщо параметр не заданий, то зупиняє всі.
     * @param array|null $keys
     */
    public function stopContainers(array $keys = null)
    {
        $this->getContainers();

        if ($keys) {
            foreach ($keys as $key) {
                $this->stopContainer($key);
            }
        } else {
            $this->stopAll();
        }

    }

    /**
     * Видаляє контейнер по id, якщо він запущений то видає exception,
     * якщо $force - true, то запущений контейнер буде запущенно і видалено.
     * @param string $containerId
     * @param bool $force
     * @return string
     * @throws Throwable
     */
    public function remove(string $containerId, bool $force = false)
    {
        if (empty($this->dockerProcess->remove($containerId))) {
            throw_if(! $force, new ContainerRunningException('Container is running'));

            $this->stopContainer($containerId);
            $this->dockerProcess->remove($containerId);
        }

        return $containerId;
    }

    /**
     * Повертає назви ствопчиків, за умови, що між назвами має бути принаймі два пробіли.
     * Якщо у назві є пробіл, то замінює його на '_'.
     *
     * @return false|string[]
     */
    private function getColumns() {
        $columns = preg_replace('/\s{2,}/', '%', $this->getOutput('header_all'));
        $columns = str_replace(' ', '_', $columns);
        return explode('%', $columns);
    }

    /**
     * Записує всі контейнери в поле $dockers.
     * За замовчуванням вказується, що всі контейнери не запущені.
     */
    private function recordAll()
    {
        $this->columns = $this->getColumns();
        $this->containers = array_map(function ($container) {
            return $this->getContainerInfo($container);
        }, $this->getOutput('all_containers'));

    }

    /**
     * Повертає результат команди 'docker ps -a'.
     * @param string $key
     * @return false|string[]
     */
    private function getOutput(string $key)
    {
        return $this->dockerProcess->getOutput($key);
    }


    /**
     * Перевіряє, які контейнери запущенні і вказує це у 'RUNNING'.
     */
    private function updateRunning()
    {
        $containers = $this->dockerProcess->getOutput('running_containers');
        $length = stripos($this->getOutput('header_running'), $this->columns[1]);
        for ($i = 0; $i < count($containers) - 1; $i++) {
            $this->setRunning($length, $containers[$i]);
        }
    }

    /**
     * Зупиняє всі запущенні контейнери.
     */
    private function stopAll()
    {
        foreach ($this->containers as $value) {
            if ($value['RUNNING']) {
                $this->stopContainer($value[$this->columns[0]]);
            }
        }
    }

    /**
     * Розбиває інформацію про контейнер на стовпики, яка міститься в $str.
     * $this-header - це рядок із назвами стовпчиків, базуючись на який ми будемо ділити інформацію в $str.
     * @param string $str
     * @return array
     */
    private function getContainerInfo(string $str)
    {
        $end = 0;
        $result = [];

        for ($i = 0; $i < count($this->columns) ; $i++) {
            $begin = $end;

            $end = isset($this->columns[$i + 1]) ?
                stripos($this->getOutput('header_all'), ($this->columns[$i + 1])) :
                strlen($str);
            $result[$this->columns[$i]] = $this->trimSubstr($str, $begin, $end);
        }

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

        $this->containers = array_map(function ($container) use ($str, $length) {
            if ($container[$this->columns[0]] === trim(substr($str, 0, $length))) {
                $container['RUNNING'] = true;
            }
            return $container;
        }, $this->containers);


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

}
