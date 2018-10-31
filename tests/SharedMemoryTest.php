<?php

use PHPUnit\Framework\TestCase;

use shs\SharedMemory\SharedMemory;

class SharedMemoryTest extends TestCase {

    protected $shm;
    
    public function setUp() {
        $key = ftok(__FILE__, substr(__FILE__, strlen(__FILE__)-1));
        $this->shm = new SharedMemory($key);
    }
    
    public function testTransaction() {
        $this->shm->set('test', 0);
        $pid = pcntl_fork();
        if($pid == 0) {
            //子进程自增 key -> 'test'
            for($i = 0; $i < 10000; $i++) {
                $this->shm->increment('test');
            }
        } else if($pid > 0) {
            //父进程自增 key -> 'test'
            for($i = 0; $i < 10000; $i++) {
                $this->shm->increment('test');
            }
            pcntl_wait($status);
            $this->assertEquals($this->shm->get('test'), 20000);
        } else {
            exit('pnctl_fork() failed');
        }
    }

    public function tearDown() {
        $this->shm->remove();
    }
}