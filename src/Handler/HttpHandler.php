<?php
/**
 * 客户端消息处理
 */
namespace shs\Handler;
use shs\Connection\ConnectionInterface;
use shs\Protocol\HttpMessage;
use shs\Protocol\HttpProtocol;

class HttpHandler {

    public function handleMessage(ConnectionInterface $connection) {

        $buffer = fread($connection->stream, $connection->recv_buffer_size);
        $connection->recv_buffer .= $buffer;
        $protocol = $connection->server->protocol;
        try {
            //计算当前数据包大小
            $connection->current_package_size = $protocol::input($connection->recv_buffer, $connection);
            if ($connection->current_package_size != 0) {
                $connection->requests++;
                $buffer = substr($connection->recv_buffer, 0, $connection->current_package_size);
                $connection->recv_buffer = substr($connection->recv_buffer, $connection->current_package_size);
                $connection->current_package_size = 0;
                $http_message = $protocol::decode($buffer, $connection);
                if($http_message['connection'] == 'close') {
                    $connection->timeout = time();
                } else {
                    $keepalive = $http_message['Keep-Alive'];
                    if(!empty($keepalive)) {
                        $keepalives = [];
                        foreach(explode(', ', $keepalive) as $item) {
                            list($key, $value) = explode('=', $item);
                            $keepalives[$key] = $value;
                        }
                    }
                    if(!empty($keepalives['timeout'])) {
                        $connection->timeout = max($connection->timeout, $keepalives['timeout'] + time());
                    }
                    if(!empty($keepalives['max'])) {
                        $connection->max_requests = max($connection->max_requests, $keepalives['max']);
                    }
                }
                if(!in_array($http_message['Method'], $connection->server->allowedMethods())) {
                    // 405
                    (new HttpCodeResponder($connection, $http_message))->sendCodeResp('405');
                    return;
                }

                switch($http_message['Method']) {
                    case 'HEAD':
                    case 'GET':
                        $rangeResponder = new RangeResponder;
                        $fileResponder = new FileResponder;
                        $rangeResponder->setNextResponder($fileResponder);

                        $chainOfResponder = $rangeResponder;
                        $onlyHeader = false;
                        if($http_message['Method'] == 'HEAD') {
                            $onlyHeader = true;
                        }
                        $chainOfResponder->respond($http_message, $connection, $onlyHeader);
                        break;
                    case 'POST':
                        break;
                    case 'OPTIONS':
                        (new HttpCodeResponder($connection, $http_message))->sendCodeResp('o200');
                        break;
                    default:
                        (new HttpCodeResponder($connection, $http_message))->sendCodeResp('405');
                }

//                if (!empty($connection->recv_buffer)) {
//                    call_user_func(array($connection, 'handleMessage'));
//                }
            }
        } catch(\Exception $e) {
            // 400
            (new HttpCodeResponder($connection, $http_message))->sendCodeResp('400');
            $connection->close();
        }
    }


}