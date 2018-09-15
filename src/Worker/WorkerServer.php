<?php
namespace shs\Worker;

use shs\SharedMemory\SharedMemory;

class WorkerServer extends Worker {

    /**
     * 工作进程数量
     * @var int
     */
    public $count = 1;


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
        $this->shm = new SharedMemory(ftok(__FILE__, 'a'));
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
        $this->createSocket();
        $this->initRuntimeVars();

        $this->startAllWorkers();
    }

    public function startAllWorkers()
    {
        $this->createWorkers();
        
    }

    public function createWorkers()
    {
        while (count($this->pids) < $this->count){
            $pid = pcntl_fork();
            if($pid == -1){
                exit("pcntl_fork fail");
            }else if($pid == 0){
//                $this->listen();
                exit($pid);
            }else{
                $this->pids[] = $pid;
                $this->shm->increment('workers');
                if(count($this->pids) == $this->count) exit;
            }
        }
    }

    public function createSocket() {
        $stream = @stream_socket_server("tcp://$this->ip:$this->port", $errno, $errstr);
        if(!$stream) {
            exit('error('.$errno.'): '.$errstr);
        }
        $this->stream = $stream;
        stream_set_blocking($this->stream, 0);
    }

    public function initRuntimeVars()
    {
        $this->shm->set('pid', 0);
        $this->shm->set('start_at', time());
        $this->shm->set('workers', 0);
        $this->shm->set('current_connections', 0);
        $this->shm->set('failed_connections', 0);
        $this->shm->set('total_connections', 0);
    }

    /**
     * 判断是否已启动
     * @return bool
     */
    protected function isRunning() {
        $pid = $this->shm->get('pid');
        if($pid > 0) {
            return true;
        } else {
            return false;
        }
    }


    public function stopServer() {
        $this->stopAllWorkers();
        $this->emptyRuntimeVars();
        $this->closeSocket();
    }


    public function stopAllWorkers() {
        foreach($this->pids as $pid) {
            exec("kill -9 $pid");
        }
    }

    public function emptyRuntimeVars() {
        $this->shm->del('pid');
        $this->shm->del('start_at');
        $this->shm->del('workers');
        $this->shm->del('current_connections');
        $this->shm->del('failed_connections');
        $this->shm->del('total_connections');
    }

    public function closeSocket() {
        if(is_resource($this->socket)) {
            fclose($this->socket);
        }
    }

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



}