<?php
/**
 * 共享内存
 */
namespace shs\SharedMemory;

class SharedMemory {
    /**
     * 共享内存标识符
     * @var resource
     */
    protected $shm;

    //信号量标识符
    protected $sem;

    //是否在事物中
    protected $in_transaction;

    protected $writingKey = 'ECapAKs62nogg28a';

    /**
     * constructor
     * @param int $key
     * @param int $memsize
     * @param int $perm
     */
    public function __construct($key, $memsize = 0, $perm = 0666) {
        if(empty($memsize)) {
            $this->shm = shm_attach($key);
        } else {
            $this->shm = shm_attach($key, $memsize, $perm);
        }
        $this->sem = sem_get($key);
        $this->in_transaction = false;
    }

    public function get($key) {
        //如果在事务中调用此函数，则锁机制交由事务来处理
        if(!$this->in_transaction) {
            while(1) {
                @sem_acquire($this->sem, true);
                if(@shm_get_var($this->shm, crc32($this->writingKey))) {
                    @sem_release($this->sem);
                    continue;
                }
                break;
            }
        }

        $value = @shm_get_var($this->shm, crc32($key));

        if(!$this->in_transaction) {
            @sem_release($this->sem);
        }

        return $value;
    }

    public function set($key, $value) {
        //事务将获取互斥锁，因此在事务中调用时无需获取锁
        if(!$this->in_transaction) {
            sem_acquire($this->sem);
            shm_put_var($this->shm, crc32($this->writingKey), true);
        }

        $result = shm_put_var($this->shm, crc32($key), $value);

        if(!$this->in_transaction) {
            shm_put_var($this->shm, crc32($this->writingKey), false);
            sem_release($this->sem);
        }

        return $result;
    }


    public function delete($key) {
        if(!$this->in_transaction) {
            sem_acquire($this->sem);
            shm_put_var($this->shm, crc32($this->writingKey), true);
        }

        $result = @shm_remove_var($this->shm, crc32($key));

        if(!$this->in_transaction) {
            shm_put_var($this->shm, crc32($this->writingKey), false);
            sem_release($this->sem);
        }

        return $result;
    }

    /**
     * 开始事务  原子和并发安全
     * @param callable $callback
     * @return mixed  返回回调的结果
     */
    public function transction($callback) {
        sem_acquire($this->sem);
        shm_put_var($this->shm, crc32($this->writingKey), true);
        $this->in_transaction = true;

        $result = call_user_func($callback, $this);

        $this->in_transaction = false;
        shm_put_var($this->shm, crc32($this->writingKey), false);
        sem_release($this->sem);

        return $result;
    }

    /**
     * 自增值
     */
    public function increment($key, $by = 1) {
        return $this->transction(function($sm) use($key, $by) {
            $value = $sm->get($key);

            if(!is_int($value)) {
                return false;
            }

            return $sm->set($key, $value+(int)$by);
        });
    }

    /**
     * 自减
     */
    public function decrement($key, $by = 1) {
        return $this->transction(function($sm) use($key, $by) {
            $value = $sm->get($key);

            if(!is_int($value)) {
                return false;
            }

            return $sm->set($key, $value-(int)$by);
        });
    }

    /**
     * 释放共享内存
     */
    public function remove() {
        shm_remove($this->shm);
        sem_remove($this->sem);
    }
}