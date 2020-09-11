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
        $this->recordAll();
        $this->updateRunning();

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
        if (empty($this->containers)) {
            $this->getContainers();
        }

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
    public function deleteContainer(string $containerId, bool $force = false)
    {
        if ('' === $this->dockerProcess->remove($containerId)) {
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
        $columns = preg_replace('/\s{2,}/', '%', $this->getHeader());
        $columns = str_replace(' ', '_', $columns);
        return explode('%', $columns);
    }

    /**
     * Записує всі контейнери в поле $dockers.
     * За замовчуванням вказується, що всі контейнери не запущені.
     */
    private function recordAll()
    {
        $containers = $this->getInfo();
        $this->columns = $this->getColumns();
        foreach ($containers as $container) {
            $this->containers[] = $this->getContainerInfo($container);
        }
    }

    /**
     * Повертає результат команди 'docker ps -a'.
     * @return false|string[]
     */
    private function getInfo()
    {
        return $this->dockerProcess->getContainerListing(true);
    }

    /**
     * @return string
     */
    private function getHeader()
    {
        return $this->dockerProcess->getHeader();
    }

    /**
     * Перевіряє, які контейнери запущенні і вказує це у 'RUNNING'.
     */
    private function updateRunning()
    {
        $containers = $this->dockerProcess->getContainerListing();
        $length = stripos($this->getHeader(), $this->columns[1]);

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

        for ($i = 0; $i < count($this->columns) - 1; $i++) {
            $begin = $end;
            $end = stripos($this->getHeader(), $this->columns[$i + 1]);
            $result[$this->columns[$i]] = $this->trimSubstr($str, $begin, $end);
        }

        $result[$this->columns[count($this->columns) - 1]] = $this->trimSubstr($str, $end, strlen($str));

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
        foreach ($this->containers as $key => $value) {
            if ($value[$this->columns[0]] === trim(substr($str, 0, $length))) {
                $this->containers[$key]['RUNNING'] = true;
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

}
