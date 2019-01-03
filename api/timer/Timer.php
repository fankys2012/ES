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

    public function msg($msg, $display = true)
    {
        if(empty($msg))
        {
            return false;
        }
        if($display)
        {
            echo date('Y-m-d H:i:s') . "   " . $msg . "\r\n<br/>";
        }
        //写入日志文件
        $str_cms_dir = dirname(dirname(dirname(__FILE__))) . '/';
        $str_log_dir = $str_cms_dir . 'data/log/nn_timer/' . $this->timer_name . '/' . date("Ymd") . "/";
        $str_log_file = date("H") . '.txt';
        $str_start_time = "[{$this->process_num}][" . date("Y-m-d H:i:s") . "]";
        if(!is_dir($str_log_dir))
        {
            mkdir($str_log_dir, 0777, true);
        }
        file_put_contents($str_log_dir . $str_log_file, $str_start_time . $msg . "\r\n", FILE_APPEND);
        return true;
    }

    public function check_linux_course()
    {
        //$str_course_file_name = str_replace($this->base_path_1.'/', '', $this->file_path);
        $str_course_file_name = APP_DIR.'/'.Base::$app->id.' '.$this->route;
        $str_course_file_name = "ps -ef | grep '" . $str_course_file_name . "' | grep -v grep | awk '{print $2}'";
        m_config::timer_write_log($this->timer_path,"进程查询的命令为：" . $str_course_file_name,$this->child_path);
        @exec($str_course_file_name,$arr_course,$exec_result);
        m_config::timer_write_log($this->timer_path,"进程查询的结果为：" . var_export($arr_course,true),$this->child_path);
        if($exec_result != 0)
        {
            global $g_ignore_exec_error;
            $return = $g_ignore_exec_error ? false : true;
            unset($g_ignore_exec_error);
            $str_desc = $return ? "程序忽略定时器进程控制，继续执行" : "程序需要定时器进程控制，停止执行";
            m_config::timer_write_log($this->timer_path,"进程查询php报错，可能是关闭了exec，也可能没在linux环境运行....".$str_desc,$this->child_path);
            return $return;
        }
        if (!empty($arr_course))
        {
            $count_course =count($arr_course);
            if($count_course == 1)
            {
                m_config::timer_write_log($this->timer_path,"运行的进程有".$count_course."个，进程正常",$this->child_path);
                return true;
            }
            else
            {
                m_config::timer_write_log($this->timer_path,"运行的进程有".$count_course."个，结束进程",$this->child_path);
                return false;
            }
        }
        else
        {
            m_config::timer_write_log($this->timer_path,"一个进程都没开启，继续执行",$this->child_path);
            return true;
        }
        m_config::timer_write_log($this->timer_path,"-------进程查询处理结束-------",$this->child_path);
    }
}