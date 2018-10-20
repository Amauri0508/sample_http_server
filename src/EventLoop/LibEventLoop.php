<?php
namespace shs\EventLoop;

class LibEventLoop implements EventLoopInterface{

    private $running;

    private $base;

    private $events;

    private $signals;

    public function __construct() {
        if(!function_exists('event_base_new')){
            exit("Not installed Libevent\n");
        }
        $this->base = event_base_new();
    }

    public function add($fd, $flag, $callback, $args = null) {
        switch($flag) {
            case self::EV_SIGNAL:
                $key = (int)$fd;

                $event = event_new();
                if(!event_set($event, $fd, EV_SIGNAL | EV_PERSIST, $callback, $args)) {
                    return false;
                }
                if(!event_base_set($event, $this->base)) {
                    return false;
                }
                if(!event_add($event)) {
                    return false;
                }

                $this->signals[$key] = $event;

                break;
            case self::EV_READ:
            case self::EV_WRITE:
                $real_flag = ($flag === self::EV_READ ? EV_READ : EV_WRITE) | EV_PERSIST;

                $key = (int)$fd;

                $event = event_new();
                if(!event_set($event, $fd, $real_flag, $callback, $args)) {
                    return false;
                }
                if(!event_base_set($event, $this->base)) {
                    return false;
                }
                if(!event_add($event)) {
                    return false;
                }

                $this->events[$key][$flag] = $event;
                break;
            default:
                return false;
        }
        return true;
    }


    public function delete($fd, $flag) {
        switch($flag) {
            case self::EV_SIGNAL:
                $key = (int)$fd;
                if(!empty($this->signals[$key])) {
                    event_del($this->signals[$key]);
                    unset($this->signals[$key]);
                }
                break;
            case self::EV_READ:
            case self::EV_WRITE:
                $key = (int)$fd;
                if(!empty($this->events[$key][$flag])) {
                    event_del($this->events[$key][$flag]);
                    unset($this->events[$key][$flag]);
                }
                if(empty($this->events[$key])) {
                    unset($this->events[$key]);
                }
                break;
            default:
                return false;
        }
        return true;
    }

    public function run() {
        $this->running = true;
        while(1) {
            if(!$this->running) {
                break;
            }
            event_base_loop($this->base, EVLOOP_ONCE); //EVLOOP_ONCE  阻塞直到有一个活跃的event，然后执行完活跃事件的回调就退出。
        }
    }

    public function stop() {
        $this->running = false;
    }
}