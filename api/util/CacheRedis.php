<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2019/1/2
 * Time: 17:44
 */

namespace api\util;

use frame\Base;

class CacheRedis
{
    private static $_redisIntance = null;

    private $_redis = null;

    private function __construct($config=[])
    {
        $this->_redis = new \Redis();
        if($this->_redis) {
            $this->_redis->connect($config['host'],$config['port'],$config['timeout']?:3);

            if(isset($config['password']) && $config['password']) {
                $result =$this->_redis->auth($config['password']);
                if(!$result) {
                    return null;
                }
            }
        }

        return $this->_redis;
    }

    /**
     * @return CacheRedis|\Redis
     */
    public static function getInstance()
    {
        if(self::$_redisIntance == null) {
            $config = Base::$app->params['redis'];
            if(empty($config)) {
                return null;
            }
            self::$_redisIntance = new CacheRedis($config);
        }
        return self::$_redisIntance;
    }


    public function __destruct()
    {
        $this->close();
    }

    public function close()
    {
        if($this->_redis) {
            $this->_redis->close();
            $this->_redis = null;
        }
    }

    public function __call($func,$params)
    {
        if (method_exists($this->_redis,$func))
        {
            return call_user_func_array(array($this->_redis,$func),$params);
        }
    }


}