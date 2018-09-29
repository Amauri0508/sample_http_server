<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/29
 * Time: 15:52
 */
namespace shs\Protocol;
use ArrayAccess;

class HttpMessage implements ArrayAccess {

    protected $headers;
    protected $body;

    public function __construct($headers, $body) {
        $this->headers = $headers;
        $this->body = $body;
    }

    public function headers()  {
        return $this->headers;
    }

    public function header($name, $value = null) {
        if(!is_null($value)) {
            $this->headers[$name] = $value;
        }

        return isset($this->headers[$name]) ? $this->headers[$name] : null;
    }

    public function body($value = null) {
        if(!is_null($value)) {
            $this->body = $value;
        }

        return $this->body;
    }

    public function offsetExists($offset) {
        return isset($this->headers[$offset]);
    }

    public function offsetGet($offset) {
        return isset($this->headers[$offset]) ? $this->headers[$offset] : null;
    }

    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->headers[] = $value;
        } else {
            $this->headers[$offset] = $value;
        }
    }

    public function offsetUnset($offset) {
        unset($this->headers[$offset]);
    }
}