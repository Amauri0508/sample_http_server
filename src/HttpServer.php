<?php
namespace shs;

use shs\Worker\WorkerServer;
use shs\Protocol\HttpProtocol;

class HttpServer extends WorkerServer {

    /**
     * HTTP协议类
     * @var string
     */
    public $protocol = HttpProtocol::class;

    public function __construct($ip, $port)
    {
        parent::__construct($ip, $port);

    }
}