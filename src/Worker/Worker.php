<?php
namespace shs\Worker;

use shs\EventLoop\LibEventLoop;
use shs\EventLoop\EventLoopInterface;
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
    }

    public function listen() {
        $this->loop->add($this->stream, EventLoopInterface::EV_READ, array($this, 'handleConnection'));
        $this->loop->add(5, EventLoopInterface::EV_TIMER, array($this, 'clearTimedOutConnections'));
        $this->loop->run();
    }



}