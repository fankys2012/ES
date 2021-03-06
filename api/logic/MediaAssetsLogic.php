<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2018/12/21
 * Time: 14:14
 */

namespace api\logic;


use api\model\KeywordsModel;
use api\model\MediaAssetsDoc;
use api\util\CacheRedis;
use frame\Base;
use frame\helpers\BaseVarDumper;
use frame\Log;

class MediaAssetsLogic
{
    public $esclient=null;

    public $mediaAsstesDocModel = null;

    public $keywordsLogic = null;

    /*
     * 媒资关联关键词_id 列表
     */
    protected $keywordsList = [];

    //var 关键词清洗规则
    protected $rinse_rule = null;

    public function __construct()
    {
        $this->esclient = ESLogic::getInstance();

        $this->mediaAsstesDocModel = new MediaAssetsDoc($this->esclient->connect());

        $rule = require APP_DIR.'/config/rule.php';
        if($rule) {
            $this->rinse_rule = unserialize($rule);
        }
        else {
            $this->rinse_rule = [];
        }


        $this->keywordsLogic = new KeywordsLogic();
    }

    public function updateMediaAssetDoc($params)
    {
        if(!isset($params['original_id']) || empty($params['original_id'])) {
            return ['ret'=>1,'reason'=>'params original_id can not empty'];
        }

        if(!isset($params['source']) || empty($params['source'])) {
            $params['source'] = 'cms';
        }

        $_id = md5($params['original_id'].$params['source']);


        $exists = $this->mediaAsstesDocModel->getDocById($_id);

        //创建关键词
        $this->keywordsList = [];
        if($params['category'] =='vod') {
            //若媒资包栏目未空 则表示未上线，未上线的数据状态置为不可用状态
            if(!isset($params['package']) || count($params['package']) <1) {
                $params['state'] = 0;
                $params['package'] = [];
            }
            elseif(isset($params['package']) && count($params['package']) >0) {
                $params['state'] = 1;
            }
            $this->createVodMediaAssetsKeywords($params);
        }
        else if($params['category'] =='star') {
            $kres = $this->createKeywords($params['name'],'star',$params['original_id'],$params['source'],$params['state']);
            if(isset($params['video_update']) && $params['video_update']) {
                $this->updateActorRelationVideo($params['video_update']);
            }
        }
        $params['kw_cites'] = $this->keywordsList;
        if(is_array($params['kw_cites'])) {
            $params['kw_cites'] = array_unique($params['kw_cites']);
        }

        if($exists['ret'] == 0 && $exists['data']['_id']) {
            $params['modify_time'] = Base::$curr_date_time;
            $fieldData = $this->mediaAsstesDocModel->getEditFieldsData($params);
            $result = $this->mediaAsstesDocModel->editDoc($fieldData,$_id);
        }
        else {
            $fieldData = $this->mediaAsstesDocModel->getAddFieldData($params);
            $result = $this->mediaAsstesDocModel->addDoc($fieldData,$_id);
        }
        //关键词更新队列
        if(isset($exists['data']['kw_cites']) && $exists['data']['kw_cites']) {
            $params['kw_cites'] = array_merge($params['kw_cites'],$exists['data']['kw_cites']);
            $params['kw_cites'] = array_unique($params['kw_cites']);
            Log::info("关键词更新列表：".BaseVarDumper::export($params['kw_cites']));
        }
        $qeresult = $this->keywordsUpdateQueue($params['kw_cites']);

        return $result;

    }

    /**
     * 通过媒资文档原始ID删除文档数据
     * @param string $id        original id
     * @param string $source    数据来源
     * @return ['ret'=0/1,'reason'=>'']
     */
    public function deleteMediaAssetDocByOriginalId($id,$source='cms')
    {
        if(empty($id)) {
            return ['ret'=>1,'reason'=>'params original_id can not empty'];
        }
        $_id = md5($id.$source);
        return $this->mediaAsstesDocModel->delDocById($_id);
    }

    /**
     * 生成关键词 媒资标题、别名、演员、导演
     * @param $params
     */
    public function createVodMediaAssetsKeywords($params)
    {
        $cites = $params['state'] ? 1:0;
        $cites_data = [
            'cp_id'=>isset($params['cp_id']) ? $params['cp_id'] : '',
            'epg_tag'=>isset($params['epg_tag']) ? $params['epg_tag'] : '',
            'original_id'=>$params['original_id'],
        ];
        if(isset($params['name']) && $params['name']) {
            $name = $this->rinseKeywords($params['name'],'vod','vod_name');
            $this->createKeywords($name,'vod','',$params['source'],$cites,$cites_data);
        }
        //别名
        if(isset($params['alias_name']) && $params['alias_name']) {
            $name = $this->rinseKeywords($params['alias_name'],'vod','vod_alias');
            $this->createKeywords($name,'vod','',$params['source'],$cites,$cites_data);
        }
        //导演
        if(isset($params['director']) && is_array($params['director']) && !empty($params['director']))
        {
            foreach ($params['director'] as $director)
            {
                $this->createKeywords($director['name'],'star',$director['id'],$params['source'],$cites);
            }
        }

        //演员
        if(isset($params['actor']) && is_array($params['actor']) && !empty($params['actor']))
        {
            foreach ($params['actor'] as $actor)
            {
                $this->createKeywords($actor['name'],'star',$actor['id'],$params['source'],$cites);
            }
        }
        return [
            'ret'=>0,
            'reason'=>'success'
        ];
    }

    /**
     * 关键词按照配置规则清洗内容
     * @param string $string
     * @param string $type
     * @param string $field
     * @return mixed
     */
    public function rinseKeywords($string,$type='vod',$field='vod_name')
    {
        $rule = [];

        if(isset($this->rinse_rule[$type][$field])) {
            $rule = $this->rinse_rule[$type][$field];
        }
        if(empty($rule)) {
            return $string;
        }
        $rinseString = trim(preg_replace($rule['patterns'],$rule['replace'],$string));
        if(empty($rinseString)) {
            Log::error("规则清洗后关键词为空".BaseVarDumper::export(['keywords'=>$string,'rule'=>$rule]));
            return $string;
        }
        return $rinseString;
    }


    /**
     * 创建关键词
     * @param $name
     * @param $category
     * @param $originalId
     * @param string $source
     * @param int $cites
     * @return array
     */
    protected function createKeywords($name,$category,$originalId,$source='cms',$cites=0,$cites_data=[])
    {
        if(empty($name)) {
            return ['ret'=>1,'reason'=>'keywords empty'];
        }
        if($originalId) {
            $_id = md5($originalId.$source);
        }
        else {
            $_id = md5($name);
        }
        $exists = $this->keywordsLogic->getById($_id);
        if($category != 'star') {
            $this->keywordsList[] = $_id;
        }

        if($exists['ret'] ==0 && $exists['data']['_id'] ) {
            //明星
            if($category =='star'){
                $data = [
                    'name'=>$name,
                    'cites_counter'=>$cites,
                ];
            }
            else {
                if(!is_array($exists['data']['category'])) {
                    $exists['data']['category']= [$category];
                }
                elseif(!in_array($category,$exists['data']['category'])){
                    array_push($exists['data']['category'],$category);
                }
                $data = [
                    'category'=>$exists['data']['category'],
                ];

            }
            return $this->keywordsLogic->updateKeywords($_id,$data);
        }
        else{
            $params = KeywordsModel::getAddFieldData($name,$category,1,$originalId,$source,$cites);

            return $this->keywordsLogic->addKeywords($params,$_id);
        }

    }

    /**
     * 搜索列表
     * @param array $query
     * @param integer $from
     * @param integer $size
     * @return array
     */
    public function getList($query,$from,$size)
    {
        $result = $this->mediaAsstesDocModel->getList($query,$from,$size);
        if($result['data']['aggs'] && is_array($result['data']['aggs'])) {
            $aggs = [];
            foreach ($result['data']['aggs'] as $key => $value) {
                foreach ($value['buckets'] as $item) {
                    if(empty($item['key'])) {
                        continue;
                    }
                    $aggs[$key][] = [
                        'name'=>$item['key'],
                        'count'=>$item['doc_count'],
                    ];
                }
            }
            $result['data']['aggs'] = $aggs;
        }
        return $result;
    }

    /**
     * 点击数更新
     * @param $params
     * @return array
     */
    public function updateMediaAssetsClicks($params)
    {
        if(!isset($params['original_id']) || empty($params['original_id'])) {
            return ['ret'=>1,'reason'=>'params original_id can not empty'];
        }

        if(!isset($params['source']) || empty($params['source'])) {
            $params['source'] = 'cms';
        }

        $_id = md5($params['original_id'].$params['source']);

        $exists = $this->mediaAsstesDocModel->getDocById($_id);
        if($exists['ret'] == 0 && $exists['data']['_id']) {
            $params['modify_time'] = Base::$curr_date_time;
            $fieldData = $this->mediaAsstesDocModel->getEditFieldsData($params);
            $result = $this->mediaAsstesDocModel->editDoc($fieldData,$_id);
        }
        else {
            return ['ret'=>1,'reason'=>'media assets not found'];
        }
        return $result;
    }

    /**
     * 关键词更新队列
     * @param array $keywordsList = ['关键词ID1','关键词ID2']
     * @return array
     */
    public function keywordsUpdateQueue($keywordsList)
    {
        if(empty($keywordsList)) {
            return ['ret'=>0,'reason'=>'keywords empty'];
        }

        $redisClient = CacheRedis::getInstance();
        if(!$redisClient) {
            return ['ret'=>1,'reason'=>'redis connect failed'];
        }
        $key = 'qe:update_keywords_list';
        foreach ($keywordsList as $item) {
            $score = $redisClient->zscore($key,$item);
            if($score) {
                continue;
            }
            $redisClient->zAdd($key,time(),$item);
        }
        return ['ret'=>0,'reason'=>'success'];

    }

    public function updateActorRelationVideo($relation)
    {
        if(empty($relation) || !is_array($relation)) {
            return ['ret'=>0,'reason'=>'empty'];
        }
        $loop = 0;
        $bulkList = [];
        foreach ($relation as $item)  {
            $_id = md5($item['original_id'].$item['source']);
            $bulkList[$_id] = [
                'director'=>(isset($item['director']) && is_array($item['director'])) ? $item['director'] :[],
                'actor'=>(isset($item['actor']) && is_array($item['actor'])) ? $item['actor'] :[],
            ];
            $loop ++;
            if($loop > 200) {
                $this->mediaAsstesDocModel->updateByBulk($bulkList);
                $bulkList = [];
                $loop = 0;
            }
        }
        if(count($bulkList) >0) {
            $this->mediaAsstesDocModel->updateByBulk($bulkList);
        }
        return ['ret'=>0,'reason'=>'success'];
    }
}