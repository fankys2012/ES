<?php
/**
 * Synchronize MediaAssets Clicks
 * User: fankys
 * Date: 2019/1/14
 * Time: 15:58
 */

namespace api\logic;


use api\model\MediaAssetsDoc;
use api\util\CacheRedis;
use frame\Base;

class ClickSyncLogic
{
    const REDISHASHKEY = 'sync:media_assets';

    protected $esclient=null;

    public $mediaAsstesDocModel = null;

    public $redis = null;



    public function __construct()
    {
        $this->esclient = ESLogic::getInstance();

        $this->mediaAsstesDocModel = new MediaAssetsDoc($this->esclient->connect());

    }

    /**
     * @param $list = [
     *  'doc id'=> [
     *      'oned_click'=>'昨日点击量',
     *      'sd_click'=>'7日点击量',
     *      'fth_click'=>'15日点击量',
     *      'm_click'=>'30日点击量'
     * ]
     * ]
     * @param bool $syncKeywords 同步更新关联关键词
     * @return array
     */
    public function syncClicks($list,$syncKeywords=false)
    {
        if(empty($list)) {
            return ['ret'=>1,'reason'=>'list empty'];
        }
        $arrId = array_keys($list);
        $result = $this->mediaAsstesDocModel->mgetById($arrId);
        if($result['ret'] == 1) {
            return $result;
        }
        //错误信息列表
        $errList = [];
        //同步更新关键词点击数
        $keyWordsList = [];

        $editDoc = [];
        foreach ($list as $key => $item){
            if(!isset($result['data'][$key])) {
                $errList[] = $item['original_id'].' 获取文档失败';
                continue;
            }
            $data = $result['data'][$key];
            if($syncKeywords == true) {
                if(isset($data['kw_cites']) && is_array($data['kw_cites'])) {
                    foreach ($data['kw_cites'] as $kw) {
                        $keyWordsList[$kw] = $item;
                    }
                }
            }

            $editDoc[$key] = [
                'oned_click'=>$item['oned_click'],
                'sd_click'=>$item['sd_click'],
                'fth_click'=>$item['fth_click'],
                'm_click'=>$item['m_click'],
            ];

        }
        if($editDoc) {
            $result = $this->mediaAsstesDocModel->updateByBulk($editDoc);
            if($result['ret'] ==1) {
                return $result;
            }
        }
        //同步关键词部分
        if($syncKeywords && $keyWordsList) {
            $kwresult = $this->syncKeywordsClick($keyWordsList);
            if($kwresult['ret'] ==1) {
                return $kwresult;
            }
        }
        if($errList) {
            return ['ret'=>1,'data'=>[
                'failedList'=>$errList,
                'successList'=>$result['data']
            ]];
        }

        return $result;
    }

    /**
     * @param $list = [
     *  'doc id'=> [
     *      'oned_click'=>'昨日点击量',
     *      'sd_click'=>'7日点击量',
     *      'fth_click'=>'15日点击量',
     *      'm_click'=>'30日点击量'
     * ]
     * ]
     * @return array
     */
    public function syncKeywordsClick($list)
    {
        if(empty($list)) {
            return ['ret'=>1,'reason'=>'list empty'];
        }
        $ids = array_keys($list);
        $redisClient = CacheRedis::getInstance();
        $history = $redisClient->hMGet(self::REDISHASHKEY,$ids);
    }
}