<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2019/3/6
 * Time: 15:38
 */

namespace api\logic;


use api\model\KeywordsModel;
use api\model\MediaAssetsDoc;
use api\util\CacheRedis;

class StatisticLogic
{

    public $esclient=null;

    public $mediaAsstesDocModel = null;

    public $keywordsLogic = null;

    public $redisInstance = null;


    public function __construct()
    {
        $this->esclient = ESLogic::getInstance();

        $this->redisInstance = CacheRedis::getInstance();

    }

    public function ContentStatistic($doc_id)
    {
        //文档查询是否存在
        $MediaAssetsDocModel = new MediaAssetsDoc($this->esclient->connect());
        $docInfo = $MediaAssetsDocModel->getDocById($doc_id);
        if($docInfo['ret'] != 0) {
            return ['ret'=>1,'reason'=>'doc not found'];
        }
        $key = 'content:'.$doc_id.':'.date('Ymd');
        $state = $this->redisInstance->incr($key);
        if(!$state) {
            return ['ret'=>1,'reason'=>'update clicks failed!'];
        }
        //score
        $score = date('ymdHi');
        $zkey = 'z:content:list';
        $this->redisInstance->zAdd($zkey,$score,$doc_id);
        return ['ret'=>0,'reason'=>'success'];

    }

    public function KeywordsStatistic($doc_id)
    {
        //文档查询是否存在
        $keywordsLogic = new KeywordsLogic();
        $docInfo = $keywordsLogic->getById($doc_id);
        if($docInfo['ret'] != 0) {
            return ['ret'=>1,'reason'=>'doc not found'];
        }
        $key = 'keyword:'.$doc_id.':'.date('Ymd');
        $state = $this->redisInstance->incr($key);
        if(!$state) {
            return ['ret'=>1,'reason'=>'update clicks failed!'];
        }
        //score
        $score = date('ymdHi');
        $zkey = 'z:keyword:list';
        $this->redisInstance->zAdd($zkey,$score,$doc_id);
        return ['ret'=>0,'reason'=>'success'];

    }


}