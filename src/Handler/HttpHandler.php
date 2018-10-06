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
                    $this->methodNotAllowed($connection, $http_message);
                    return;
                }

                switch($http_message['Method']) {
                    case 'HEAD':
                    case 'GET':
                        print_r($http_message);exit;
                        break;
                    case 'POST':
                        break;
                    case 'OPTIONS':
                        $optionsResponse = new HttpMessage([
                            'Code' => '200',
                            'Status' => HttpProtocol::$status['200'],
                            'Version' => $http_message['Version'],
                            'Date' => date('D, d m Y H:i:s e'),
                            'Allow' => implode(', ', $connection->server->allowedMethods())
                        ], '');
                        $connection->sendString($optionsResponse);
                        break;
                    default:
                        $this->methodNotAllowed($connection, $http_message);
                }

                if (!empty($connection->recv_buffer)) {
                    call_user_func(array($connection, 'handleMessage'));
                }
            }
        } catch(\Exception $e) {
            // 400
            $this->badRequest($connection);
            $connection->close();
        }
    }


    /**
     * 请求不允许
     * @param $connection
     * @param $http_message
     */
    private function methodNotAllowed($connection, $http_message)
    {
        $body = <<<EOF
<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html><head>
<title>405 Method Not Allowed</title>
</head><body>
<h1>Method Not Allowed</h1>
</body></html>
EOF;
        $methodNotAllowedResponse = new HttpMessage([
            'Code' => '405',
            'Status' => HttpProtocol::$status['405'],
            'Version' => $http_message['Version'],
            'Content-Type' => 'text/html',
            'Content-Length' => strlen($body),
            'Date' => date('D, d m Y H:i:s e')
        ], $body);
        $connection->sendString($methodNotAllowedResponse);
    }

    /**
     * 请求错误
     * @param $connection
     */
    private function badRequest($connection)
    {
        $body = <<<EOF
<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html><head>
<title>400 Bad Request</title>
</head><body>
<h1>Bad Request</h1>
</body></html>
EOF;
        $badRequestResponse = new HttpMessage([
            'Code' => '400',
            'Status' => HttpProtocol::$status['400'],
            'Version' => 'HTTP/1.1',
            'Content-Type' => 'text/html',
            'Content-Length' => strlen($body),
            'Date' => date('D, d m Y H:i:s e')
        ], $body);
        $connection->sendString($badRequestResponse);
    }


}