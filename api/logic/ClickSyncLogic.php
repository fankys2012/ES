<?php
/**
 * Synchronize MediaAssets Clicks
 * User: fankys
 * Date: 2019/1/14
 * Time: 15:58
 */

namespace api\logic;


use api\model\KeywordsModel;
use api\model\MediaAssetsDoc;
use api\util\CacheRedis;
use frame\Base;
use frame\helpers\BaseVarDumper;
use frame\Log;

class ClickSyncLogic
{
    const REDISHASHKEY = 'sync:media_assets';

    protected $esclient=null;

    public $mediaAsstesDocModel = null;

    public $keywordsModel = null;

    public $redis = null;



    public function __construct()
    {
        $this->esclient = ESLogic::getInstance();

        $this->mediaAsstesDocModel = new MediaAssetsDoc($this->esclient->connect());

        $this->keywordsModel = new KeywordsModel($this->esclient->connect());

    }

    /**
     * @param $list = [
     *  'doc id'=> [
     *      'oned_click'=>'昨日点击量',
     *      'sd_click'=>'7日点击量',
     *      'fth_click'=>'15日点击量',
     *      'm_click'=>'30日点击量'
     *      'sd_avg_click'=>'7日均量',
     *      'fth_agv_click'=>'15日均量',
     *      'm_agv_click'=>'30日均量',
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
        Log::trace("更新列表数据");
        Log::trace(BaseVarDumper::export($list));

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
                        if(isset($keyWordsList[$kw])) {
                            $keyWordsList[$kw] = [
                                'oned_click'=>$item['oned_click']+$keyWordsList[$kw]['oned_click'],
                                'sd_click'=>$item['sd_click']+$keyWordsList[$kw]['sd_click'],
                                'fth_click'=>$item['fth_click']+$keyWordsList[$kw]['fth_click'],
                                'm_click'=>$item['m_click']+$keyWordsList[$kw]['m_click'],
                                'sd_avg_click'=>$item['sd_avg_click']+$keyWordsList[$kw]['sd_avg_click'],
                                'fth_agv_click'=>$item['fth_agv_click']+$keyWordsList[$kw]['fth_agv_click'],
                                'm_agv_click'=>$item['m_agv_click']+$keyWordsList[$kw]['m_agv_click'],
                            ];
                        }
                        else {
                            $keyWordsList[$kw] = $item;
                        }

                    }
                }
            }

            $editDoc[$key] = [
                'oned_click'=>$item['oned_click']>1 ? $item['oned_click']:1,
                'sd_click'=>$item['sd_click']>1 ? $item['sd_click']:1,
                'fth_click'=>$item['fth_click']>1 ? $item['fth_click']:1,
                'm_click'=>$item['m_click']>1 ? $item['m_click']:1,
                'sd_avg_click'=>$item['sd_avg_click']>1 ? $item['sd_avg_click']:1,
                'fth_agv_click'=>$item['fth_agv_click']>1 ? $item['fth_agv_click']:1,
                'm_agv_click'=>$item['m_agv_click']>1 ? $item['m_agv_click']:1,
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
     *      'm_click'=>'30日点击量',
     *      'sd_avg_click'=>'7日均量',
     *      'fth_agv_click'=>'15日均量',
     *      'm_agv_click'=>'30日均量',
     * ]
     * ]
     * @return array
     */
    public function syncKeywordsClick($list)
    {
        if(empty($list)) {
            return ['ret'=>1,'reason'=>'list empty'];
        }

        Log::trace("关键词更新列表数据");
        Log::trace($list);

        //获取关键词点击量
        $ids = array_keys($list);
        $redisClient = CacheRedis::getInstance();
        $history = $redisClient->hMGet(self::REDISHASHKEY,$ids);

        $cbKeyVal = [];
        if(is_array($history)) {
            foreach ($history as $key => $item) {
                if($item == false) {
                    $cbKeyVal[$key] = json_encode($list[$key]);
                    continue;
                }
                $data = json_decode($item,true);

                $list[$key]['oned_click'] += $data['oned_click'];
                $list[$key]['sd_click'] += $data['sd_click'];
                $list[$key]['fth_click'] += $data['fth_click'];
                $list[$key]['m_click'] += $data['m_click'];
                $list[$key]['sd_avg_click'] += $data['sd_avg_click'];
                $list[$key]['fth_agv_click'] += $data['fth_agv_click'];
                $list[$key]['m_agv_click'] += $data['m_agv_click'];

                $cbKeyVal[$key] = json_encode($list[$key]);
            }
        }
        //数据保存redis
        $res = $redisClient->hMset(self::REDISHASHKEY,$cbKeyVal);
        Log::info("与redis合并后的数据为");
        Log::info($list);
        foreach ($list as $key =>$value)
        {
            $editDoc = [
                'oned_click'=>$value['oned_click']>1 ? $value['oned_click']:1,
                'sd_click'=>$value['sd_click']>1 ? $value['sd_click']:1,
                'fth_click'=>$value['fth_click']>1 ? $value['fth_click']:1,
                'm_click'=>$value['m_click']>1 ? $value['m_click']:1,
                'sd_avg_click'=>1,
                'fth_agv_click'=>1,
                'm_agv_click'=>1,
            ];
            if($value['sd_click']) {
                $editDoc['sd_avg_click'] = ceil($value['sd_click']/7);
            }
            if($value['fth_agv_click']) {
                $editDoc['fth_agv_click'] = ceil($value['fth_click']/15);
            }
            if($value['m_agv_click']) {
                $editDoc['m_agv_click'] = ceil($value['m_click']/30);
            }
            $list[$key] = $editDoc;
        }
        Log::info("更新关键词点击数列表");
        Log::info($list);

        //更新文档
        $result = $this->keywordsModel->updateByBulk($list);
        return $result;
    }
}