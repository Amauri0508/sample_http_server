<?php
namespace shs;

use shs\Worker\Worker;

class HttpServer extends Worker {

    public function __construct($ip, $port)
    {
        parent::__construct($ip, $port);

    }
}