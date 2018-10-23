<?php

use shs\Protocol\HttpMessage;
require_once __DIR__ . '/vendor/autoload.php';

// 创建http server
$http = new shs\HttpServer('127.0.0.1', 8899);
// worker进程数量
$http->count = 1;
// 以守护进程启动
$http->deamon = false;
// $http->onConnection = function($conn, $data){
//     print_r($conn);
// };

// $conn 当前连接， $data 接收到的http消息 (shs\Protocol\HttpMessage)
$http->onMessage = function($conn, $data){
    // print_r($data);
    
    $conn->send(new HttpMessage([
        'Code' => '200',
        'Status' => 'success',
        'Version' => 'HTTP/1.1',
        'Content-Type' => 'text/html',
        'Content-Length' => '',
        'Date' => date('D, d m Y H:i:s e')
    ], "hello"));
};



// 虚拟主机
$http->hosts = array(
    'default' => [
        'root' => '/www/html/',
        'index' => 'index.php index.html index.htm'
    ],
);

$http->run();
