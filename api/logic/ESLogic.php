<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2018/12/3
 * Time: 14:18
 */

namespace api\logic;


use Elasticsearch\ClientBuilder;
use frame\Base;
use frame\helpers\FileHelper;

class ESLogic
{
    private static $_ESInstance = null;

    //@var \Elasticsearch\Client
    private $client = null;

    private function __construct()
    {

    }

    public static function getInstance()
    {
        if(self::$_ESInstance == null) {
            self::$_ESInstance = new ESLogic();
        }
        return self::$_ESInstance;
    }

    /**
     * @return \Elasticsearch\Client
     */
    public function connect($flush=false)
    {
        if($this->client === null || $flush == true)
        {
            $log_file_path = $this->logFilePath();
            $hosts = Base::$app->params['es'];
            $level = Base::$app->params['es_log_level'];
            $logger = ClientBuilder::defaultLogger($log_file_path,$level);
            $this->client = ClientBuilder::create()->setHosts($hosts)->setLogger($logger)->build();
        }
        return $this->client;
    }

    /**
     * 防止克隆
     */
    public function __clone()
    {
        // TODO: Implement __clone() method.
    }

    protected function logFilePath()
    {
        $current_time = time();
        $int_time=$current_time - ($current_time % 300);
        $str_date_time=date("Ymd", $int_time).'T'.date("His",$int_time);
        $log_file_path = APP_DIR.'/tmp/es/'.date('Ym').'/'.date('d');
        if(!is_dir($log_file_path)) {
            FileHelper::createDirectory($log_file_path,0755,true);
        }
        $log_file = $log_file_path.'/'.$str_date_time.'.log';
        return $log_file;
    }

}