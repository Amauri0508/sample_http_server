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
    /**
     * 配置虚拟主机根目录
     * @var array
     */
    public $hosts = array();

    public function __construct($ip, $port)
    {
        parent::__construct($ip, $port);
        $this->handler = new HttpHandler();
    }


    /**
     * 获取允许的请求方法
     * @return array
     */
    public function allowedMethods() {
        return $this->methods;
    }

    /**
     * 虚拟主机配置
     * @param $host
     * @return array|mixed
     */
    public function getHostConfig($host) {
        if(empty($host)) {
            return isset($this->hosts['default']) ? $this->hosts['default'] : [];
        }
        return isset($this->hosts[$host]) ? $this->hosts[$host] : (isset($this->hosts['default']) ? $this->hosts['default'] : []);
    }
}