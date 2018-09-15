<?php
namespace shs\Worker;

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
    }





}