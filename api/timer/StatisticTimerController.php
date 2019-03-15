<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2019/3/7
 * Time: 11:22
 */

namespace api\timer;


use api\logic\ESLogic;
use api\model\ESKeywordsModel;
use api\model\MediaAssetsDoc;
use api\util\CacheRedis;
use frame\helpers\BaseVarDumper;
use frame\Log;

class StatisticTimerController extends Timer
{
    /**
     * @var \api\util\CacheRedis|\Redis
     */
    protected $redisInstance = null;
    protected $esKeywordsModel = null;

    protected $mediaAssetsModel = null;

    public function keywordsAction()
    {
        if(false === $this->check_linux_course()) {
            return false;
        }
        $this->redisInstance = CacheRedis::getInstance();
        $zsortKey = 'z:keyword:list';
        //删除60天前的记录
        $delScore = date('ymdHi',(time()-86400*60));
        $this->redisInstance->zremrangebyscore($zsortKey,0,$delScore);
        Log::info('统计关键词点击量开始');

        $esIntances = ESLogic::getInstance()->connect();
        $this->esKeywordsModel = new ESKeywordsModel($esIntances);

        $page = 0;
        $pageSize = 200;
        while (true) {
            $start = ($page) * $pageSize;
            $page ++;
            $result = $this->redisInstance->zRange($zsortKey,$start,$pageSize);
            if(empty($result)) {
                Log::info("获取第{$page}次时，数据获取失败，结束统计");
                break;
            }
            $arrDocId = [];
            foreach ($result as $value){
                $arrDocId[] = $value;
            }
            $docList = $this->esKeywordsModel->mgetById($arrDocId);
            $this->_doKeywords($arrDocId,$docList['data']);
            unset($docList,$result,$arrDocId);

        }
        Log::info("关键词统计结束，当前执行{$page}次");

    }

    /**
     * 关键词统计
     * @param $arrDocId
     * @param $docList
     */
    private function _doKeywords(&$arrDocId,&$docList)
    {
        //redis 中存在 ，文档中不存在，则需要删除redis中的数据
//        $delMembers = [];
        if(empty($docList) || !is_array($docList)) {
            return;
        }
        $updateDoc = [];
        foreach ($arrDocId as $id) {
            if(!isset($docList[$id])) {
                Log::info("文档ID：{$id} 不存在，从zsort中删除");
//                $delMembers[] = $id;
                $this->redisInstance->zDelete('z:keyword:list',$id);
                continue;
            }

            $clicks = $this->_getCountFromRedis('keyword',$id);
            Log::info("文档:{$id} 点击数据为".BaseVarDumper::export($clicks));
            $docClick = [
                't_click'      => $docList[$id]['t_click'] + $clicks['realClicks'],//总点击数
                'oned_click'   => $clicks['lastClicks'],//1日点击量
                'sd_click'     => $clicks['sevenClicks'],//7日点击量
                'sd_avg_click' => $clicks['vsevenClicks'],//7日日均点击量
                'fth_click'    => $clicks['fifteenClicks'],//15日点击量
                'fth_agv_click'=> $clicks['vfifteenClicks'],//15日日均点击量
                'm_click'      => $clicks['monthClicks'],//30日点击量
                'm_agv_click'  => $clicks['vmonthClicks'],//30日日均点击量
            ];
            $updateDoc[$id] = $docClick;
        }
        Log::info("更新关键词文档点击数的数据为:".BaseVarDumper::export($updateDoc));
        $this->esKeywordsModel->updateByBulk($updateDoc);
        return;
    }

    /**
     * 文档统计
     * @param $arrDocId
     * @param $docList
     */
    private function _doContents(&$arrDocId,&$docList)
    {
        //redis 中存在 ，文档中不存在，则需要删除redis中的数据
//        $delMembers = [];
        if(empty($docList) || !is_array($docList)) {
            return;
        }
        $updateDoc = [];
        foreach ($arrDocId as $id) {
            if(!isset($docList[$id])) {
                Log::info("文档ID：{$id} 不存在，从zsort中删除");
//                $delMembers[] = $id;
                $this->redisInstance->zDelete('z:content:list',$id);
                continue;
            }

            $clicks = $this->_getCountFromRedis('content',$id);
            Log::info("文档:{$id} 点击数据为".BaseVarDumper::export($clicks));
            $docClick = [
                't_click'      => $docList[$id]['t_click'] + $clicks['realClicks'],//总点击数
                'oned_click'   => $clicks['lastClicks'],//1日点击量
                'sd_click'     => $clicks['sevenClicks'],//7日点击量
                'sd_avg_click' => $clicks['vsevenClicks'],//7日日均点击量
                'fth_click'    => $clicks['fifteenClicks'],//15日点击量
                'fth_agv_click'=> $clicks['vfifteenClicks'],//15日日均点击量
                'm_click'      => $clicks['monthClicks'],//30日点击量
                'm_agv_click'  => $clicks['vmonthClicks'],//30日日均点击量
            ];
            $updateDoc[$id] = $docClick;
        }
        Log::info("更新文档点击数的数据为:".BaseVarDumper::export($updateDoc));
        $this->mediaAssetsModel->updateByBulk($updateDoc);
        return;
    }


    /**
     * 从redis中获取统计数
     * @param string $prefix redis key前缀
     * @param string $docId  文档id
     * @return return [
                'lastClicks' =>$lastDayClicks,
                'sevenClicks' =>$sevenClicks,
                'fifteenClicks' =>$fifteenClicks,
                'monthClicks'=>$monthClicks,
                'vsevenClicks'=>$sv ,
                'vfifteenClicks'=> $fv ,
                'vmonthClicks' => $mv
                ];
     */
    private function _getCountFromRedis($prefix,$docId)
    {
        $currTime = time();
        $getKeys = [];
        for ($i=1;$i<31;$i++) {
            $key = date('Ymd',($currTime - $i * 86400));
            $getKeys[] = $prefix.":".$docId.":".$key;
        }
        $result = $this->redisInstance->mget($getKeys);
        $sevenClicks = 0;
        $fifteenClicks = 0;
        $monthClicks = 0;
        $lastDayClicks = $result[0] ?: 0 ;
        foreach ($result as $key=> $value) {
            if($value) {
                $monthClicks += $value;
                if($key <7) {
                    $sevenClicks += $value;
                }
                if($key <15) {
                    $fifteenClicks += $value;
                }
            }
        }
        $fv = ceil($fifteenClicks/15);
        $sv = ceil($sevenClicks/7);
        $mv = ceil($monthClicks/30);
        return [
            'lastClicks' =>$lastDayClicks >0 ? $lastDayClicks : 1,
            'sevenClicks' =>$sevenClicks >0 ? $sevenClicks: 1,
            'fifteenClicks' =>$fifteenClicks >0 ? $fifteenClicks : 1,
            'monthClicks'=>$monthClicks >0 ? $monthClicks : 1,
            'vsevenClicks'=>$sv  > 0 ? $sv: 1,
            'vfifteenClicks'=> $fv  > 0 ? $fv: 1,
            'vmonthClicks' => $mv  >0 ? $mv: 1,
            'realClicks' => $lastDayClicks,
        ];
    }

    public function contentAction()
    {
        if(false === $this->check_linux_course()) {
            return false;
        }
        $this->redisInstance = CacheRedis::getInstance();
        $zsortKey = 'z:content:list';
        //删除60天前的记录
        $delScore = date('ymdHi',(time()-86400*60));
        $this->redisInstance->zremrangebyscore($zsortKey,0,$delScore);
        Log::info('统计内容点击量开始');

        $esIntances = ESLogic::getInstance()->connect();
        $this->mediaAssetsModel = new MediaAssetsDoc($esIntances);

        $page = 0;
        $pageSize = 200;
        while (true) {
            $start = ($page) * $pageSize;
            $page ++;
            $result = $this->redisInstance->zRange($zsortKey,$start,$pageSize);
            if(empty($result)) {
                Log::info("获取第{$page}次时，数据获取失败，结束统计");
                break;
            }
            $arrDocId = [];
            foreach ($result as $value){
                $arrDocId[] = $value;
            }
            $docList = $this->mediaAssetsModel->mgetById($arrDocId);
            $this->_doContents($arrDocId,$docList['data']);
            unset($docList,$result,$arrDocId);

        }
        Log::info("内容统计结束，当前执行{$page}次");

    }
}