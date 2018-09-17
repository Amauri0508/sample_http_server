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

    public $loop;
    public $handler;

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

    private function createConnection() {
        return new Connection($this);
    }

}