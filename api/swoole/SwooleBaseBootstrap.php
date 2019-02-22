<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2019/2/18
 * Time: 10:36
 */

namespace api\swoole;


use frame\Base;
use frame\Log;

abstract class SwooleBaseBootstrap
{
    /**
     * @var \frame\swoole\server\Server
     */
    protected $server;

    protected $workId;

    public $appConfig;

    /**
     * @var Container
     */
    protected static $container;

    /**
     * @var callable
     */
    public $init;

    public function __construct($server)
    {
        $this->server = $server;
        $this->init();
    }
    public function init(){}

    public abstract function handleRequest($request,$reponse);


    public function onRequest($request,$reponse)
    {
        $result = $this->handleRequest($request,$reponse);
    }

    public function onWorkerStart($server,$workerId)
    {
        $this->workId = $workerId;
        $initFunc = $this->init;
        if ($initFunc instanceof \Closure) {
            $initFunc($this);
        }
        self::$container = new \frame\di\Container();
        Base::$container = self::$container;
//        \frame\Log::setLogger(self::$container->get('frame\log\Logger'));
    }


    public function onWorkerError($swooleServer, $workerId, $workerPid, $exitCode, $sigNo)
    {
        Log::error("worker error happening [workerId=$workerId, workerPid=$workerPid, exitCode=$exitCode, signalNo=$sigNo]...", 'monitor');
    }

    public function onTask($server, $taskId, $srcWorkerId, $data)
    {
        $func = array_shift($data);
        if (is_callable($func)) {
            $params[] = array_shift($data);
            call_user_func_array($func, $params);
        }
        return 1;
    }

    public function onFinish($server, $taskId, $data)
    {
        //echo $data;
    }

    public function onWorkerStop($server,$workerId)
    {

    }

}