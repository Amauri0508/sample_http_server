<?php
namespace shs\Protocol;

use shs\Connection\ConnectionInterface;

class HttpProtocol implements ProtocolInterface {
    /**
     * HTTP状态码
     * @var array
     */
    public static $status = [
        '100' => 'Continue',
        '101' => 'Switching ',
        '102' => 'Processing',
        '200' => 'OK',
        '201' => 'Created',
        '202' => 'Accepted',
        '203' => 'Non-Authoritative Information',
        '204' => 'No Content',
        '205' => 'Reset Content',
        '206' => 'Partial Content',
        '207' => 'Multi-Status',
        '300' => 'Multiple Choices',
        '301' => 'Moved Permanently',
        '302' => 'Found',
        '303' => 'See Other',
        '304' => 'Not Modified',
        '305' => 'Use Proxy',
        '306' => 'Unused',
        '307' => 'Temporary Redirect',
        '400' => 'Bad Request',
        '401' => 'Unauthorized',
        '402' => 'Payment Required',
        '403' => 'Forbidden',
        '404' => 'Not Found',
        '405' => 'Method Not Allowed',
        '406' => 'Not Acceptable',
        '407' => 'Proxy Authentication Required',
        '408' => 'Request Time-out',
        '409' => 'Conflict',
        '410' => 'Gone',
        '411' => 'Length Required',
        '412' => 'Precondition Failed',
        '413' => 'Request Entity Too Large',
        '414' => 'Request-URI Too Large',
        '415' => 'Unsupported Media Type',
        '416' => 'Requested range not satisfiable',
        '417' => 'Expectation Failed',
        '500' => 'Internal Server Error',
        '501' => 'Not Implemented',
        '502' => 'Bad Gateway',
        '503' => 'Service Unavailable',
        '504' => 'Gateway Time-out',
        '505' => 'HTTP Version not supported'
    ];

    public static function input($buffer, ConnectionInterface $connection) {

        if(($pos = strpos($buffer, "\r\n\r\n")) !== false) {
            //请求验证
            if(!self::validateRequest($buffer)) {
                echo '400 Bad Request';
            }

            $header_string = substr($buffer, 0, $pos);
            //获得请求请求体长度
            $result = preg_match('/Content-Length\: (\d*)(?:\r\n)?/i', $header_string, $matches);
            if($result) {
                $body_len = $matches[1];
            } else {
                $body_len = 0;
            }

            $request_len = $pos + 4 + $body_len;

            if($request_len > strlen($buffer)) {
                return 0;
            } else {
                return $request_len;
            }
        } else {
            return 0;
        }
    }

    public static function encode($original, ConnectionInterface $connection) {
        $respond = "$original[Version] $original[Code] $original[Status]\r\n";
        foreach($original->headers() as $name => $value) {
            if(!in_array($name, ['Version', 'Code', 'Status'])) {
                $respond .= "$name: $value\r\n";
            }
        }

        return "$respond\r\n".$original->body();
    }

    public static function decode($buffer, ConnectionInterface $connection) {
        $pos = strpos($buffer, "\r\n\r\n");
        $header_string = substr($buffer, 0, $pos);
        $body = strval(substr($buffer, $pos+4));

        $headers = self::parseHeader($header_string);

        return new HttpMessage($headers, $body);
    }

    /**
     * 验证http请求
     * @param string $request
     * @return bool
     */
    protected static function validateRequest($request) {
        $pattern = '/^(?:GET|POST|PUT|HEAD|DELETE|OPTIONS|TRACE|CONNECT|LINK|UNLINE) .*? HTTP\/1\.[10]\r\n(?:.*?\: .*?\r\n)*?\r\n/mi';

        return preg_match($pattern, $request);
    }

    /**
     * 解析header
     * @param $header
     * @return array
     */
    protected static function parseHeader($header) {
        $headers = array();
        $header_lines = explode("\r\n", $header);
        $request_line = array_shift($header_lines);
        list($headers['Method'], $headers['Uri'], $headers['Version']) = explode(' ', $request_line);
        array_walk($header_lines, function($item) use (&$headers){
            list($name, $value) = explode(': ', $item);
            $headers[$name] = $value;
        });
        return $headers;
    }
}