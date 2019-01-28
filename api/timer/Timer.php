<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2019/1/3
 * Time: 13:50
 */

namespace api\timer;


use frame\Base;
use frame\console\Controller;
use frame\Log;
use frame\helpers\BaseVarDumper;


class Timer extends Controller
{

    protected $route = null;

    public function __construct($id, $module, array $config = [])
    {
        parent::__construct($id, $module, $config);

        $route = Base::$app->request->resolve();
        if(isset($route[0]) && $route[0]) {
            $this->route = $route[0];
        }
        $logPath =APP_DIR.'/tmp/timer/'.Base::$app->id.$this->route.'/'.date("Ymd", FRAME_DATE_TIME);
        $logFile = date("H",FRAME_DATE_TIME).'.log';
        Log::setLogPath($logPath,$logFile);
        unset($logPath,$logFile);
    }

    public function msg($msg)
    {
        Log::warning($msg);
        return;
    }

    protected function check_linux_course()
    {
        $execFile = APP_DIR.'/'.Base::$app->id.' '.$this->route;
        $str_course_file_name = "ps -ef | grep '" . $execFile . "' | grep -v grep | awk '{print $2}'";
        $this->msg("进程查询的命令为：" . $str_course_file_name);
        @exec($str_course_file_name,$arr_course,$exec_result);
        $this->msg("进程查询的结果为：" . BaseVarDumper::export($arr_course));
        if($exec_result != 0)
        {
            $this->msg("进程查询php报错，可能是关闭了exec，也可能没在linux环境运行....");
            return false;
        }
        if (!empty($arr_course))
        {
            $count_course =count($arr_course);
            if($count_course == 1)
            {
                $this->msg("运行的进程有".$count_course."个，进程正常");
                return true;
            }
            else
            {
                $this->msg("运行的进程有".$count_course."个，结束进程");
                $execTime = "ps -eo pid,lstart,etime,cmd | grep '{$execFile}'";
                @exec($execTime,$execTimeList,$execTimeResult);
                if($execTimeResult !=0) {
                    Log::warning("进程查询失败：".$execTime);
                } else {
                    Log::warning("进程信息：".BaseVarDumper::export($execTimeList));
                }
                return false;
            }
        }
        else
        {
            $this->msg("一个进程都没开启，继续执行");
            return true;
        }
    }
}