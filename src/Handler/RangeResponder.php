<?php
namespace shs\Handler;

use shs\Protocol\HttpProtocol;
use shs\Protocol\HttpMessage;
use shs\Connection\ConnectionInterface;

class RangeResponder {
    public function respond(HttpMessage $message, ConnectionInterface $connection, $onlyHeader = false) {

        if(empty($message['Range'])) {
            return $this->next($message, $connection, $onlyHeader);
        }

        //获取虚拟主机配置
        $host_config = $connection->server->getHostConfig($message['Host']);
        if(empty($host_config)) {
            echo "You haven't configure the hosts yet.\r\n";
            return;
        }

        //获取请求文件路径
        $uri = $message['Uri'];
        @list($request_file, $query_string) = explode('?', $uri);
        $request_file = rtrim($host_config['root'], '/').'/'.ltrim($request_file, '/.');

        //如果文件不存在，发送404
        if(!file_exists($request_file)) {
            (new HttpCodeResponder($connection, $message))->sendCodeResp('404');
        } else {
            if(is_dir($request_file)) {
                $index = @$host_config['index'];
                $indexes = explode(' ', $index);

                foreach($indexes as $index) {
                    $tmp_file = $request_file.'/'.$index;
                    if(is_file($tmp_file)) {
                        break;
                    }
                }

                if(is_file($tmp_file)) {
                    $request_file = $tmp_file;
                } else {
                    (new HttpCodeResponder($connection, $message))->sendCodeResp('403');
                    return;
                }
            }

            if(!is_readable($request_file)) {
                (new HttpCodeResponder($connection, $message))->sendCodeResp('403');
            } else {
                $ext = pathinfo($request_file, PATHINFO_EXTENSION);
                //get the mime type
                $mime = $connection->server->getMimeType($ext);

                if(preg_match('/^bytes=(\d*?)-(\d*?)$/i', $message['Range'], $matches)) {
                    $start = $matches[1];
                    $end = $matches[2];

                    //check whether the range is correct
                    if(!empty($end) && $end > filesize($request_file)) {
                        //if the range is incorrect, return 416 response
                        (new HttpCodeResponder($connection, $message))->sendCodeResp('416');

                        return;
                    }

                    //send the header first
                    $response = new HttpMessage([
                        'Code' => '206',
                        'Status' => HttpProtocol::$status['206'],
                        'Version' => $message['Version'],
                        'Content-Type' => $mime,
                        'Content-Length' => filesize($request_file) - $start,
                        'Last-Modified' => date('D, d m Y H:i:s e', filemtime($request_file)),
                        'Date' => date('D, d m Y H:i:s e'),
                        'Content-Range' => "bytes $start-$end/" . filesize($request_file)
                    ], '');
                }
                $connection->sendString($response);

                if($onlyHeader == false) {
                    //send the body
                    $fd = fopen($request_file, 'rb');
                    if (!$fd) {
                        $connection->close();
                        return;
                    }
                    fseek($fd, $start);
                    $connection->sendFile($fd);
                }
            }
        }
    }
}