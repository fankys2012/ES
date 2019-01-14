<?php
/**
 * 媒资点击数同步
 * User: fankys
 * Date: 2019/1/14
 * Time: 17:42
 */

namespace api\timer;


use api\logic\ClickSyncLogic;
use frame\Base;
use frame\helpers\FtpClient;
use frame\helpers\FtpException;

class ClickSyncController extends Timer
{
    public function syncAction()
    {
        if(false === $this->check_linux_course()) {
            return false;
        }
        $startTime = microtime(true);
        $ftpConf = Base::$app->params['clickSyncFtp'];
        $ftpClient = new FtpClient();
        try{
            $ftpClient->connect($ftpConf['address'],false,$ftpConf['port'],$ftpConf['time_out']);
        }
        catch (FtpException $e){
            $this->msg("Ftp 连接失败 ".$e->getMessage());
            return false;
        }
        try{
            $ftpClient->login($ftpConf['user'],$ftpConf['password']);
        }
        catch (FtpException $e) {
            $this->msg("Ftp 登录失败 ".$e->getMessage());
            return false;
        }
        $this->msg("Ftp 连接耗时：".(microtime(true)-$startTime));

        $file = 'media_'.Base::$app->params['clickSyncCp'].'_'.date('Ymd',(time()-86400)).'.txt';
        $localFile = APP_DIR.'/tmp/'.$file;
        $remoteFile = $ftpConf['root_dir'].'/'.$file;

        //检查远程文件是否存在
        $checkStartTime = microtime(true);
        $size = $ftpClient->size($remoteFile);
        $this->msg("检查远程文件是否存在{$remoteFile}耗时：".(microtime(true)-$checkStartTime));
        unset($checkStartTime);
        if($size<=0) {
            $this->msg("远程文件不存在");
            return false;
        }

        $downStartTime = microtime(true);
        $ftpClient->pasv(true);
        $ftpClient->get($localFile,$remoteFile,FTP_ASCII);
        $this->msg("下载远程文件{$remoteFile}耗时：".(microtime(true)-$downStartTime));

        $localFileSize = filesize($localFile);
        $this->msg("本地文件{$localFile}大小：{$localFileSize}");
        if($localFileSize <=0) {
            unlink($localFile);
            $this->msg("文件下载失败，文件大小为：{$localFileSize}");
            return;
        }

        //读取文件
        $fp = fopen($localFile,'r');
        if(!$fp) {
            unlink($localFile);
            $this->msg("本地文件打开失败");
            return;
        }
        $size = 0;
        $list = [];
        $clickSyncLogic = new ClickSyncLogic();

        while (($buffer = fgets($fp, 4096)) !== false) {
            $arr = explode("|",$buffer);
            if(!isset($arr[2]) || !$arr[2]) {
                continue;
            }
            $key = md5($arr[2].'cms');
            $list[$key] = [
                'original_id'=>$arr[2],
                'oned_click'=>isset($arr[3]) ? $arr[3] : 0,
                'sd_click'=>isset($arr[4]) ? $arr[4] : 0,
                'fth_click'=>isset($arr[5]) ? $arr[5] : 0,
                'm_click'=>isset($arr[6]) ? $arr[6] : 0,
            ];
            $size ++;
            if($size>100) {
                $size = 0;
                $result = $clickSyncLogic->syncClicks($list);
                if($result['ret'] !=0) {
                    $this->msg(var_export($result,true));
                }
                $list = [];
            }
        }
        if (!feof($fp)) {
            $this->msg("Error: unexpected fgets() fail");
        }
        fclose($fp);
        unlink($localFile);

        $result = $clickSyncLogic->syncClicks($list);
        if($result['ret'] !=0) {
            $this->msg(var_export($result,true));
        }
        $this->msg("数据同步结束");

    }
}