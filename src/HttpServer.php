<?php
namespace shs;

use shs\Worker\WorkerServer;
use shs\Protocol\HttpProtocol;
use shs\Handler\HttpHandler;

class HttpServer extends WorkerServer {

    /**
     * HTTP协议类
     * @var string
     */
    public $protocol = HttpProtocol::class;

    /**
     * 允许的请求方法
     * @var array
     */
    protected $methods = ['GET', 'POST', 'HEAD', 'OPTIONS'];

    public function __construct($ip, $port)
    {
        parent::__construct($ip, $port);
        $this->handler = new HttpHandler();
    }


    public function allowedMethods() {
        return $this->methods;
    }

}