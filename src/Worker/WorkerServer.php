<?php
namespace ssh\Worker\WorkerServer;
use shs\Worker\Worker;
use shs\SharedMemory\SharedMemory;

class WorkerServer extends Worker {

    /**
     * 工作进程数量
     * @var int
     */
    public $workers = 4;

    /**
     * 是否开启守护进程
     * @var bool
     */
    public $deamon = true;

    /**
     * 守护进程标准输出重定向到文件
     * @var
     */
    public $std_output;

    /**
     * worker进程id
     * @var array
     */
    protected $pids = array();

    /**
     * 共享内存
     * @var resource
     */
    public $shm;

    /**
     * 检测环境
     */
    public static function checkEnv()
    {
        if (php_sapi_name() != "cli") {
            exit("only run in command line mode." . PHP_EOL);
        }
        if(PATH_SEPARATOR == ';'){
            exit("only run in linux." . PHP_EOL);
        }
    }

    public function run()
    {
        self::checkEnv();
        $this->createSharedMemory();
        $this->parseCommand();
    }

    /**
     * 创建共享内存
     */
    public function createSharedMemory()
    {
        $this->shm = new SharedMemory(__FILE__, 'A');
    }

    public function parseCommand()
    {
        global $argc, $argv;
        if($argc < 2){
            exit("command: php scriptName start | stop" . PHP_EOL);
        }
        switch ($argv[1]){
            case 'start' :
                if($this->isRunning()){
                    exit("The server is already running" . PHP_EOL);
                }
                $this->startServer();
                break;
            case 'stop' :
                if($this->isRunning()){
                    exit("The server is not running" . PHP_EOL);
                }
                $this->stopServer();
                break;
            default :
                exit("command: php scriptName start | stop" . PHP_EOL);
        }
    }


    public function startServer()
    {

    }


}