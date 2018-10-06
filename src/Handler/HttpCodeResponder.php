<?php
namespace shs\Handler;
use shs\Protocol\HttpProtocol;
use shs\Protocol\HttpMessage;

class HttpCodeResponder {
    private $connection;
    private $httpMessage;

    public function __construct($connection, $httpMessage)
    {
        $this->connection = $connection;
        $this->httpMessage = $httpMessage;
    }

    public function sendCodeResp($code)
    {
        switch ($code){
            case '404':
                $this->notFoundResponse();
            case '400':
                $this->badRequest();
            case '405':
                $this->methodNotAllowed();
            case 'o200':
                $this->OptionsMethodResp();
            case '403':
                $this->forbiddenResponse();
            case '416':
                $this->rangeNotSatisfiableResponse();
        }

    }

    private function methodNotAllowed()
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
            'Version' => $this->httpMessage['Version'],
            'Content-Type' => 'text/html',
            'Content-Length' => strlen($body),
            'Date' => date('D, d m Y H:i:s e')
        ], $body);
        $this->connection->sendString($methodNotAllowedResponse);
    }

    private function notFoundResponse()
    {
        $body = $this->httpMessage['Method'] == 'HEAD' ? '' : "<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 2.0//EN\"><html><head><title>404 Not Found</title></head><body><h1>Not Found</h1><p>The requested URL was not found on this server.</p></body></html>";
        $notFoundResponse = new HttpMessage([
            'Code' => '404',
            'Status' => HttpProtocol::$status['404'],
            'Version' => $this->httpMessage['Version'],
            'Content-Type' => 'text/html',
            'Content-Length' => strlen($body),
            'Date' => date('D, d m Y H:i:s e')
        ], $body);
        $this->connection->sendString($notFoundResponse);
    }

    private function badRequest()
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
        $this->connection->sendString($badRequestResponse);
    }

    private function OptionsMethodResp()
    {
        $optionsResponse = new HttpMessage([
            'Code' => '200',
            'Status' => HttpProtocol::$status['200'],
            'Version' => $this->httpMessage['Version'],
            'Date' => date('D, d m Y H:i:s e'),
            'Allow' => implode(', ', $this->connection->server->allowedMethods())
        ], '');
        $this->connection->sendString($optionsResponse);
    }

    private function forbiddenResponse()
    {
        $body = $this->httpMessage['Method'] == 'HEAD' ? '' : <<<EOF
<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html><head>
<title>403 Forbidden</title>
</head><body>
<h1>Forbidden</h1>
</body></html>
EOF;
        $forbiddenResponse = new HttpMessage([
            'Code' => '403',
            'Status' => HttpProtocol::$status['403'],
            'Version' => $this->httpMessage['Version'],
            'Content-Type' => 'text/html',
            'Content-Length' => strlen($body),
            'Date' => date('D, d m Y H:i:s e')
        ], $body);
        $this->connection->sendString($forbiddenResponse);
    }

    private function rangeNotSatisfiableResponse()
    {
        $body = $this->httpMessage['Method'] == 'HEAD' ? '' : <<<EOF
<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html><head>
<title>416 Requested range not satisfiable</title>
</head><body>
<h1>Requested range not satisfiable</h1>
</body></html>
EOF;
        $rangeNotSatisfiableResponse = new HttpMessage([
            'Code' => '416',
            'Status' => HttpProtocol::$status['416'],
            'Version' => $this->httpMessage['Version'],
            'Content-Type' => 'text/html',
            'Content-Length' => strlen($body),
            'Date' => date('D, d m Y H:i:s e')
        ], $body);
        $this->connection->sendString($rangeNotSatisfiableResponse);
    }



}