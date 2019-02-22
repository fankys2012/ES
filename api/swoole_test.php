<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2019/2/14
 * Time: 18:10
 */

use frame\swoole\server\Server;

require_once (__DIR__ . '/../vendor/autoload.php');
require_once (__DIR__ . '/../vendor/frame/SwooleInit.php');
define('APP_DIR',__DIR__);
\frame\Base::setAlias('@api', dirname(__DIR__) . '/api');

$config = [
    'class' => 'frame\swoole\server\HttpServer',
//    'timeout'=>2,
    'setting' => [
        'daemonize' => false,
//        'max_coro_num' => 3000,
//        'reactor_num' => 1,
//        'worker_num' => 1,
//        'task_worker_num' => 2,
        'pid_file' => __DIR__ . '/tmp/SwooleHttp.pid',
//        'log_file' => __DIR__ . '/yii2-app-basic/runtime/logs/swoole.log',
//        'debug_mode' => 0,
        'enable_coroutine' => COROUTINE_ENV

    ],
];
$command = $argv[1] ?: 'start' ;

Server::run($command,$config, function (Server $server) {

    $application = new api\swoole\SwooleWebBootstrap($server);
    $application->init = function (api\swoole\SwooleBaseBootstrap $bootstrap) {
        require_once (__DIR__ . '/../vendor/autoload.php');
//        require_once (__DIR__ . '/../vendor/frame/Init.php');

        $arr_config = frame\helpers\ArrayHelper::merge(
            require(__DIR__ . '/config/main.php'),
            require (__DIR__ . '/config/config.php')
        );
        $bootstrap->appConfig = $arr_config;
        echo "init func \r\n";
    };
    //如果需要swoole Server
    $server->getSwoole()->on("Task", function (swoole_server $serv, $task_id, $from_id, $data) {
        echo "Tasker进程接收到数据";
        echo "#{$serv->worker_id}\tonTask: [PID={$serv->worker_pid}]: task_id=$task_id, data_len=" . strlen($data) . "." . PHP_EOL;
        $serv->finish($data);
    }
    );
    $server->getSwoole()->on("Finish", function (swoole_server $serv, $task_id, $data) {
        echo "Task#$task_id finished, data_len=" . strlen($data) . PHP_EOL;
    });
    $server->bootstrap = $application;
    $server->start();
});