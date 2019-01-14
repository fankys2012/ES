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

    }

    public function msg($msg)
    {
        if(empty($msg))
        {
            return false;
        }
        //写入日志文件
        $str_log_dir = APP_DIR.'/tmp/timer/'.Base::$app->id.$this->route.'/'.date("Ymd").'/';
        $str_log_file = date("H") . '.txt';
        $str_start_time = "[" . date("Y-m-d H:i:s") . "]";
        if(!is_dir($str_log_dir))
        {
            mkdir($str_log_dir, 0777, true);
        }
        file_put_contents($str_log_dir . $str_log_file, $str_start_time . $msg . "\r\n", FILE_APPEND);
        return true;
    }

    protected function check_linux_course()
    {
        //$str_course_file_name = str_replace($this->base_path_1.'/', '', $this->file_path);
        $str_course_file_name = APP_DIR.'/'.Base::$app->id.' '.$this->route;
        $str_course_file_name = "ps -ef | grep '" . $str_course_file_name . "' | grep -v grep | awk '{print $2}'";
        $this->msg("进程查询的命令为：" . $str_course_file_name);
        @exec($str_course_file_name,$arr_course,$exec_result);
        $this->msg("进程查询的结果为：" . var_export($arr_course,true));
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