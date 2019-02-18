<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2019/2/15
 * Time: 15:37
 */

namespace frame\swoole\server;

use Swoole;
class Server
{
    protected $id;
    /**
     * @var \Swoole\Server
     */
    protected $swoole;

    protected $serverType = 'http';

    /**
     * @var string
     */
    public $host = '0.0.0.0';

    public $port = 9501;
    /**
     * @var int 请求超时设置,以秒为单位
     * @deprecated 该设置不再有
     */
    public $timeout = 0;

    /**
     * @var \frame\swoole\web\Application
     */
    public $application;
    /**
     * @var string root directory
     */
    public $root;
    /**
     * @var int swoole's process mode
     */
    public $swooleMode;
    /**
     * @var int swoole socket
     */
    public $sockType;
    /**
     * @var array swoole setting
     * @see https://wiki.swoole.com/wiki/page/274.html
     */
    public $setting = [];

    public function __construct(array $config = [])
    {
        $this->parseConfig($config);
        $this->init();
    }

    protected function parseConfig(&$config)
    {
        if(isset($config['setting'])) {
            $this->setting = $config['setting'];
        }
    }

    /**
     * 服务初始化
     */
    public function init()
    {
        switch ($this->serverType){
            case 'http':
                $this->swoole = new Swoole\Http\Server($this->host,$this->port);
                $events = ['Request','WorkerError'];
                break;
            case 'websocket':
                $this->swoole = new Swoole\WebSocket\Server($this->host,$this->port);
                $events = ['Open','Message','Close','HandShake'];
                break;
            default:
                $this->swoole = new Swoole\Server($this->host,$this->port,$this->swooleMode,$this->sockType);
                $events    = ['ManagerStart', 'ManagerStop', 'PipeMessage','Packet',
                    'Receive', 'Connect', 'Close', 'Timer', 'WorkerStop', 'WorkerError'];
                break;
        }

        $this->swoole->set($this->setting);

        $this->swoole->on('Start',[$this,'onStart']);
        $this->swoole->on('Shutdown',[$this,'onShutdown']);
        $this->swoole->on('WorkerStart',[$this,'onWorkerStart']);
        $this->swoole->on('WorkerStop',[$this,'onWorkerStop']);
        if(isset($this->setting['task_worker_num'])){
//            $this->swoole->on('Task',[$this,'onTask']);
//            $this->swoole->on('Finish',[$this,'onFinish']);
        }
        foreach ($events as $event){
            if(method_exists($this,'on'.$event)){
                $this->swoole->on($event,[$this,'on'.$event]);
            }
        }
    }



    public function getSwoole()
    {
        return $this->swoole;
    }

    /**
     * start swoole server
     */
    public function start()
    {
        $this->swoole->start();
    }

    /**
     * @see https://wiki.swoole.com/wiki/page/p-event/onStart.html
     */
    public function onStart()
    {
        $this->setProcessTitle($this->id,'master');
    }

    /**
     *
     * @param Swoole\Server $server
     * @param $worker_id
     */
    public function onWorkerStart(Swoole\Server $server,$worker_id)
    {
        if(function_exists('opcache_reset')){
            opcache_reset();
        }
        if($worker_id >= $server->setting['worker_num']) {
            $this->setProcessTitle($this->id,'task process');
        } else {
            $this->setProcessTitle($this->id,'worker process');
        }
        try{
            if($this->bootstrap){
                $this->bootstrap->onWorkerStart($server,$worker_id);
            }
        }catch (\Exception $e){
            print_r("start error:".ErrorHandler::convertExceptionToString($e).PHP_EOL);
            $this->swoole->shutdown();
            die;
        }
    }

    public function onWorkerStop(Swoole\Server $server,$worker_id)
    {
        if($this->bootstrap){
            $this->bootstrap->onWorkerStop($server,$worker_id);
        }
    }

    /**
     * @param Swoole\Server $server
     * @see https://wiki.swoole.com/wiki/page/45.html
     */
    public function onShutdown(Swoole\Server $server)
    {
        echo 'swoole server shutdown';
    }

    /**
     * set process title
     * @param $siteName
     * @param $channelName
     */
    public function setProcessTitle($siteName,$channelName)
    {
        //低版本Linux内核和Mac OSX不支持进程重命名
        //@see https://wiki.swoole.com/wiki/page/125.html
        @swoole_set_process_name("php $siteName :$channelName swoole process");
    }

    protected static function autoCreate($config)
    {
        if(isset($config['class'])){
            $class = $config['class'];
            unset($config['class']);
            $instance = new $class($config);
            if($instance instanceof Server){
                return $instance;
            }
            throw new \Exception('class must implement api\swooleServer\server');
        }else{
            throw new \Exception("config 'class' not found");
        }

    }

    public static function run($cmd,$config,callable $func)
    {
        if($cmd == 'start') {
            $server = Server::autoCreate($config);
            $func($server);
        }
    }
}