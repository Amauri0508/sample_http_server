<?php
namespace shs\Worker;

use shs\EventLoop\LibEventLoop;
use shs\EventLoop\EventLoopInterface;
use SplObjectStorage;
use shs\Connection\Connection;

class Worker {
    public $stream;
    public $ip;
    public $port;

    public $onConnection;
    public $onMessage;
    public $onClose;
    public $onError;
    public $connections;

    /**
     * 事件循环
     * @var LibEventLoop
     */
    public $loop;

    /**
     * HTTP操作
     */
    public $handler;

    /**
     * Worker constructor.
     * @param $ip
     * @param $port
     */
    public function __construct($ip, $port) {
        $this->ip = $ip;
        $this->port = $port;
        $this->loop = new LibEventLoop();
        $this->connections = new SplObjectStorage();
    }

    public function listen() {
        $this->loop->add($this->stream, EventLoopInterface::EV_READ, array($this, 'handleConnection'));
        $this->loop->run();
    }

    /**
     * 客户端连接操作
     */
    public function handleConnection() {
        try {
            $connection = $this->createConnection();
        } catch(\Exception $e) {
            exit($e->getMessage());
        }
        $this->connections->attach($connection);
        stream_set_blocking($connection->stream, 0);
        $this->loop->add($connection->stream, EventLoopInterface::EV_READ, array($connection, 'handleMessage'));
    }

    /**
     * 创建客户端连接对象
     * @return Connection
     */
    private function createConnection() {
        return new Connection($this);
    }

}