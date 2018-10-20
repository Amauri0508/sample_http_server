<?php
require_once __DIR__ . '/vendor/autoload.php';

// 创建http server
$http = new shs\HttpServer('127.0.0.1', 8899);
// worker进程数量
$http->count = 2;
// 以守护进程启动
$http->deamon = true;
// 虚拟主机
$http->hosts = array(
    'default' => [
        'root' => '/www/html/',
        'index' => 'index.php index.html index.htm'
    ],
);

$http->run();