<?php
namespace shs\handler;

use shs\Protocol\HttpProtocol;
use shs\Protocol\HttpMessage;
use shs\Connection\ConnectionInterface;

class FileResponder extends AbstractResponder {
    public function respond(HttpMessage $message, ConnectionInterface $connection, $onlyHeader = false) {
        $host_config = $connection->server->getHostConfig($message['Host']);
        if(empty($host_config)) {
            echo "You haven't configure the hosts yet.\r\n";
            return;
        }

        $uri = $message['Uri'];
        @list($request_file, $query_string) = explode('?', $uri);
        $request_file = rtrim($host_config['root'], '/').'/'.ltrim($request_file, '/.');

        if(!file_exists($request_file)) {
            //文件不存在
            (new HttpCodeResponder($connection, $message))->sendCodeResp('404');

        } else {
            if (is_dir($request_file)) {
                $index = @$host_config['index'];
                $indexes = explode(' ', $index);

                foreach ($indexes as $index) {
                    $tmp_file = $request_file . '/' . $index;
                    if (is_file($tmp_file)) {
                        break;
                    }
                }

                if (is_file($tmp_file)) {
                    $request_file = $tmp_file;
                } else {
                    (new HttpCodeResponder($connection, $message))->sendCodeResp('403');
                    return;
                }
            }

            if (!is_readable($request_file)) {
                (new HttpCodeResponder($connection, $message))->sendCodeResp('403');
            } else {
                $ext = pathinfo($request_file, PATHINFO_EXTENSION);
                $mime = $connection->server->getMimeType($ext);

                $response = new HttpMessage([
                    'Code' => '200',
                    'Status' => HttpProtocol::$status['200'],
                    'Version' => $message['Version'],
                    'Content-Type' => $mime,
                    'Content-Length' => filesize($request_file),
                    'Last-Modified' => date('D, d m Y H:i:s e', filemtime($request_file)),
                    'Date' => date('D, d m Y H:i:s e'),
                ], '');
                $connection->sendString($response);

                if ($onlyHeader == false) {
                    $fd = fopen($request_file, 'rb');
                    if (!$fd) {
                        $connection->close();
                        return;
                    }
                    $connection->sendFile($fd);
                }
            }
        }
    }
}