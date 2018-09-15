<?php
namespace shs;

use shs\Worker\WorkerServer;

class HttpServer extends WorkerServer {

    public function __construct($ip, $port)
    {
        parent::__construct($ip, $port);

    }
}