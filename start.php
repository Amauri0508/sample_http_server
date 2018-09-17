<?php
require_once __DIR__ . '/vendor/autoload.php';

$http = new shs\HttpServer('127.0.0.1', 8899);
$http->count = 2;

$http->run();