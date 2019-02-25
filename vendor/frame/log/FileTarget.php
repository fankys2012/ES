<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2019/1/17
 * Time: 11:47
 */

namespace frame\log;


use frame\Base;
use frame\helpers\FileHelper;
use frame\base\InvalidConfigException;


class FileTarget extends Target
{
    public $logFile;

    public $enableRotation = true;
    /**
     * @var int maximum log file size, in kilo-bytes. Defaults to 10240, meaning 10MB.
     */
    public $maxFileSize = 10240; // in KB
    /**
     * @var int number of log files used for rotation. Defaults to 5.
     */
    public $maxLogFiles = 5;

    public $fileMode;

    public $dirMode = 0775;

    public $rotateByCopy = true;

    /**
     * Initializes the route.
     * This method is invoked after the route is created by the route manager.
     */
    public function init()
    {
        parent::init();

        //app unique code number
        if(isset($_REQUEST['trace_id']) && $_REQUEST['trace_id']) {
            $this->traceCode = $_REQUEST['trace_id'];
        } else {
            $this->traceCode = FRAME_BEGIN_TIME.mt_rand(1000,9999);
        }
        $this->createLogFile();

    }

    /**
     * 在swoole模式下 Log对象为常驻内存，故在onReques 时必须重新设置log
     */
    public function createLogFile()
    {
        if(isset(Base::$app->params['logPath']) && Base::$app->params['logPath']) {
            $logPath = Base::$app->params['logPath'];
        } else {
            $logPath = dirname(dirname(FRAME_PATH)).'/tmp/log';
        }
        $currTime = time();
        $logPath .= "/".date('Ym',$currTime)."/".date('d',$currTime);
        if(!is_dir($logPath)){
            FileHelper::createDirectory($logPath,$this->dirMode,true);
        }
        $intTime=$currTime - ($currTime % 300);
        $this->logFile = $logPath .'/'.date("Ymd", $intTime).'T'.date("His",$intTime).'.log';


        if ($this->maxLogFiles < 1) {
            $this->maxLogFiles = 1;
        }
        if ($this->maxFileSize < 1) {
            $this->maxFileSize = 1;
        }
    }



    public function export()
    {
        $text = implode("\n", array_map([$this, 'formatMessage'], $this->messages)) . "\n";
        if (($fp = @fopen($this->logFile, 'a')) === false) {
            throw new InvalidConfigException("Unable to append to log file: {$this->logFile}");
        }
        @flock($fp, LOCK_EX);
        if ($this->enableRotation) {
            // clear stat cache to ensure getting the real current file size and not a cached one
            // this may result in rotating twice when cached file size is used on subsequent calls
            clearstatcache();
        }
        if ($this->enableRotation && @filesize($this->logFile) > $this->maxFileSize * 1024) {
            $this->rotateFiles();
            @flock($fp, LOCK_UN);
            @fclose($fp);
            @file_put_contents($this->logFile, $text, FILE_APPEND | LOCK_EX);
        } else {
            @fwrite($fp, $text);
            @flock($fp, LOCK_UN);
            @fclose($fp);
        }
        if ($this->fileMode !== null) {
            @chmod($this->logFile, $this->fileMode);
        }
    }

    /**
     * Rotates log files.
     */
    protected function rotateFiles()
    {
        $file = $this->logFile;
        for ($i = $this->maxLogFiles; $i >= 0; --$i) {
            // $i == 0 is the original log file
            $rotateFile = $file . ($i === 0 ? '' : '.' . $i);
            if (is_file($rotateFile)) {
                // suppress errors because it's possible multiple processes enter into this section
                if ($i === $this->maxLogFiles) {
                    @unlink($rotateFile);
                } else {
                    if ($this->rotateByCopy) {
                        @copy($rotateFile, $file . '.' . ($i + 1));
                        if ($fp = @fopen($rotateFile, 'a')) {
                            @ftruncate($fp, 0);
                            @fclose($fp);
                        }
                        if ($this->fileMode !== null) {
                            @chmod($file . '.' . ($i + 1), $this->fileMode);
                        }
                    } else {
                        @rename($rotateFile, $file . '.' . ($i + 1));
                    }
                }
            }
        }
    }
    public function setLogPath($path,$file='')
    {
        if(!is_dir($path)){
            FileHelper::createDirectory($path,$this->dirMode,true);
        }
        if(empty($file)) {
            $intTime=FRAME_DATE_TIME - (FRAME_DATE_TIME % 300);
            $file = date("Ymd", $intTime).'T'.date("His",$intTime).'.log';
        }
        $this->logFile = $path.'/'.$file;
    }
}