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

    /**
     * 开启所有服务
     */
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

    /**
     * 解析用户命令
     */
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

    /**
     * 开启所有服务
     */
    public function startServer()
    {
        $this->createSocket();
        $this->initRuntimeVars();
        $this->startAllWorkers();
    }

    public function startAllWorkers()
    {
        $this->createWorkers();
        $this->handleSignals();
    }

    /**
     * 创建工作子进程
     */
    public function createWorkers()
    {
        while (count($this->pids) < $this->count){
            $pid = pcntl_fork();
            if($pid == -1){
                exit("pcntl_fork fail");
            }else if($pid == 0){
                $this->listen();
            }else{
                $this->pids[] = $pid;
                $this->shm->increment('workers');
                if(count($this->pids) == $this->count) return;
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

    /**
     * 初始化运行时变量
     */
    public function initRuntimeVars()
    {
        $this->shm->set('pid', 0);
        $this->shm->set('workers', 0);
    }

    /**
     * 销毁共享内存
     */
    public function destroySharedMemory() {
        $this->shm->remove();
    }

    /**
     * 注册信号
     */
    public function handleSignals() {
        $this->initSignals();
        $this->shm->set('pid', posix_getpid());
        while(1) {
            //开始分发
            pcntl_signal_dispatch();
        }
    }

    /**
     * 注册信号和处理器
     */
    public function initSignals() {
        pcntl_signal(SIGINT, array($this, 'signalHandler'));
        pcntl_signal(SIGHUP, array($this, 'signalHandler'));
        pcntl_signal(SIGUSR1, array($this, 'signalHandler'));
        pcntl_signal(SIGCHLD, array($this, 'signalHandler'));
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

    /**
     * 信号处理器
     * @param $signal
     */
    public function signalHandler($signal) {
        switch($signal) {
            case SIGINT:
            case SIGHUP:
                // 停止信号
                $this->stopServer();
                $this->destroySharedMemory();
                exit;
                break;
            case SIGUSR1:
                $this->stopServer();
                exit;
                break;
            case SIGCHLD:
                //子进程结束时, 父进程会收到这个信号。
                $pid = pcntl_wait($status, WNOHANG);
                if($pid > 0) {
                    foreach($this->pids as $key => $value) {
                        if($value == $pid) {
                            unset($this->pids[$key]);
                        }
                    }
                }
                // workers - 1
                $this->shm->decrement('workers');
                break;
        }
    }

    /**
     * 停止所有
     */
    public function stopServer() {
        $this->stopAllWorkers();
        $this->emptyRuntimeVars();
        $this->closeSocket();
    }


    /**
     * 结束所有进程
     */
    public function stopAllWorkers() {
        foreach($this->pids as $pid) {
            exec("kill -9 $pid");
        }
    }

    /**
     * 清空运行时变量
     */
    public function emptyRuntimeVars() {
        $this->shm->delete('pid');
        $this->shm->delete('workers');
    }

    /**
     * 关闭socket资源
     */
    public function closeSocket() {
        if(is_resource($this->stream)) {
            fclose($this->stream);
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