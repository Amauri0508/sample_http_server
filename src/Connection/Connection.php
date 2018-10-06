<?php
namespace shs\Connection;

use shs\EventLoop\EventLoopInterface;

class Connection implements ConnectionInterface{

    public $stream;

    /**
     * 此连接所属的工作进程
     * @var
     */
    public $server;

    /**
     * 接收缓冲
     * @var string
     */
    public $recv_buffer = '';

    /**
     * 接收缓冲大小
     * @var int
     */
    public $recv_buffer_size = 1048576;

    /**
     * 发送缓冲
     * @var string
     */
    protected $send_buffer = '';

    /**
     * @var array
     */
    protected $fds = [];

    /**
     * 当前数据包的大小
     * @var int
     */
    public $current_package_size;

    public $timeout = 0;

    public $max_requests = 0;

    public $requests = 0;

    protected $connected_at;

    protected $last_recv_time;


    public function __construct($server)
    {
        $this->server = $server;
        $this->stream = @stream_socket_accept($this->server->stream, 5, $peername);
        if(!$this->stream) {
            if(is_callable($this->server->onError)) {
                call_user_func($this->server->onError, $this, "create connection to $peername failed."); exit;
            }
        }
        stream_set_read_buffer($this->stream, 0);
        $this->connected_at = $this->last_recv_time = time();
    }

    /**
     * 接收客户端消息
     */
    public function handleMessage() {
        $this->server->handler->handleMessage($this);
    }

    /**
     * 向客户端发送消息
     */
    public function send($buffer, $raw = false) {
        if($buffer) {
            if(!$raw) {
                $protocol = $this->server->protocol;
                $buffer = $protocol::encode($buffer, $this);
            }

            $len = strlen($buffer);
            $writeLen = 0;
            while($writeLen < $len) {
                $data = @fwrite($this->stream, substr($buffer, $writeLen, 8192), 8192);
                if($data === false) {
                    return $writeLen;
                } else if($data === 0) {
                    $this->close();
                    return $writeLen;
                }

                $writeLen += $data;
            }

            return $writeLen;
        }

        return 0;
    }


    /**
     * 将字符串发送到客户端
     * @param mixed $buffer
     * @param bool $raw
     */
    public function sendString($buffer, $raw = false) {
        if($buffer) {
            if(!$raw) {
                $protocol = $this->server->protocol;
                $buffer = $protocol::encode($buffer, $this);
            }
            $writeLen = $this->send($buffer, true);
            if($writeLen < strlen($buffer)) {
                if($this->isSendBufferEmpty()) {
                    call_user_func(array($this, 'onSendBufferNotEmpty'));
                }
                $this->send_buffer .= substr($buffer, $writeLen);
            }
        }
    }

    public function tooManyRequests() {
        if($this->max_requests > 0 && $this->requests >= $this->max_requests) {
            return true;
        }
        return false;
    }

    /**
     * 关闭连接
     */
    public function close() {
        $this->server->connections->detach($this);
        $this->server->loop->delete($this->stream, EventInterface::EV_READ);
        fclose($this->stream);
    }

    /**
     * 连接超时
     * @return bool
     */
    public function timedOut() {
        if($this->timeout > 0) {
            if(time() >= $this->timeout) {
                return true;
            }
        } else {
            if(time() >= ($this->connected_at + $this->server->connectionTimeout)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 检查发送缓冲区是否为空
     * @return bool
     */
    public function isSendBufferEmpty() {
        if($this->send_buffer != '') {
            return false;
        }
        foreach($this->fds as $fd) {
            if(!feof($fd)) {
                return false;
            }
        }
        return true;
    }


    public function flushSendBuffer() {
        if($this->send_buffer == '') {
            foreach ($this->fds as $key => $fd) {
                if (feof($fd)) {
                    fclose($fd);
                    unset($this->fds[$key]);
                    continue;
                }
                $this->send_buffer .= fread($fd, 8192);
                break;
            }
        }

        $writeLen = $this->send($this->send_buffer, true);
        $this->send_buffer = substr($this->send_buffer, $writeLen);

        //如果发送缓冲区为空，则取消写入事件
        if($this->isSendBufferEmpty()) {
            call_user_func(array($this, 'onSendBufferEmpty'));
        }
    }

    /**
     * 当发送缓冲区为空时调用
     */
    public function onSendBufferEmpty() {
        $this->server->loop->delete($this->stream, EventLoopInterface::EV_WRITE);
    }

    /**
     * 当发送缓冲区不为空时调用
     */
    public function onSendBufferNotEmpty() {
        $this->server->loop->add($this->stream, EventLoopInterface::EV_WRITE, array($this, 'flushSendBuffer'));
    }

    /**
     * send file to the client
     * @param resource $fd  the file handler
     */
    public function sendFile($fd) {
        if($this->isSendBufferEmpty()) {
            call_user_func(array($this, 'onSendBufferNotEmpty'));
        }
        $this->fds[] = $fd;
    }

    /**
     * 获取客户端地址，包括IP和端口
     * @return string
     */
    public function getRemoteAddress() {
        return stream_socket_get_name($this->stream, true);
    }

    /**
     * 获取客户端IP
     * @return string
     */
    public function getRemoteIp() {
        return substr($this->getRemoteAddress(), 0, strpos($this->getRemoteAddress(), ':'));
    }

    /**
     * 获取客户端端口
     * @return string
     */
    public function getRemotePort() {
        return substr($this->getRemoteAddress(), strpos($this->getRemoteAddress(), ':')+1);
    }
}