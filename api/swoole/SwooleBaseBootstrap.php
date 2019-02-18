<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2019/2/18
 * Time: 10:36
 */

namespace api\swoole;


abstract class SwooleBaseBootstrap
{
    /**
     * @var \frame\swoole\server\Server
     */
    protected $server;

    protected $workId;

    public $appConfig;

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

    public function onWorkerStop($server,$workerId)
    {

    }


}