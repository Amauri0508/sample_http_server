<?php
use shs\Protocol\HttpMessage;
use PHPUnit\Framework\TestCase;
class HttpMessageTest extends TestCase{


    public function testReturn(){

        $headers = [
            'Code' => '400',
            'Status' => 'Bad Request',
            'Version' => 'HTTP/1.1',
            'Content-Type' => 'text/html',
            'Content-Length' => 5,
            'Date' => date('D, d m Y H:i:s e')
        ];
        $httpMsg = new HttpMessage($headers, 'abcde');

        $this->assertEquals($httpMsg->header('Code'), 400);

        $this->assertEquals($httpMsg->body("defg"), "defg");

        $this->assertEquals($httpMsg["Code"], 400);

        $httpMsg["Code"] = 500;

        $this->assertEquals($httpMsg["Code"], 500);

        $this->assertEquals($httpMsg["Version"], "HTTP/1.1");



    }

}




