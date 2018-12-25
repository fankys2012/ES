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
use frame\Base;

class MediaAssetsLogic
{
    public $esclient=null;

    public $mediaAsstesDocModel = null;

    protected $keywordsLogic = null;

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
        //若媒资包栏目未空 则表示未上线，未上线的数据状态置为不可用状态
        if(count($params['package']) <1) {
            $params['state'] = 0;
        }
        else {
            $params['state'] = 1;
        }

        $exists = $this->mediaAsstesDocModel->getDocById($_id);
        if($exists['ret'] == 0 && $exists['data']['_id']) {
            $params['modify_time'] = Base::$curr_date_time;
            $result = $this->mediaAsstesDocModel->editDoc($params,$_id);
        }
        else {
            $params['modify_time'] = Base::$curr_date_time;
            $params['create_time'] = Base::$curr_date_time;
            $result = $this->mediaAsstesDocModel->addDoc($params,$_id);
        }
        //创建关键词
        $this->createVodMediaAssetsKeywords($params);
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
        if(isset($params['name']) && $params['name']) {
            $name = $this->rinseKeywords($params['name'],'vod','vod_name');
            $this->createKeywords($name,'vod','',$params['source'],$cites);
        }
        //别名
        if(isset($params['alias_name']) && $params['alias_name']) {
            $name = $this->rinseKeywords($params['alias_name'],'vod','vod_alias');
            $this->createKeywords($name,'vod','',$params['source'],$cites);
        }
        //导演
        if(is_array($params['director']) && !empty($params['director']))
        {
            foreach ($params['director'] as $director)
            {
                $this->createKeywords($director['name'],'star',$director['id'],$params['source'],$cites);
            }
        }
        //演员
        if(is_array($params['actor']) && !empty($params['actor']))
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
        return trim(preg_replace($rule['patterns'],$rule['replace'],$string));
    }


    protected function createKeywords($name,$category,$originalId,$source='cms',$cites=0)
    {
        if($originalId) {
            $_id = md5($originalId.$source);
        }
        else {
            $_id = md5($name);
        }
        $exists = $this->keywordsLogic->getById($_id);

        if($exists['ret'] ==0 && $exists['data']['_id'] ) {
            //如果明星 且存在则不更新
            if($category =='star'){
                return $exists;
            }
            else {
                if(in_array($category,$exists['data']['category'])) {
                    return $exists;
                }
                array_push($exists['data']['category'],$category);
                $data = [
                    'category'=>$exists['data']['category']
                ];
                return $this->keywordsLogic->updateKeywords($_id,$data);
            }
        }
        else{
            $params = array(
                'name'         => $name,
                'category'     => [$category],
                'state'        => 1,//状态 1：启用 0：禁用
                'create_time'  => Base::$curr_date_time, //创建时间
                'modify_time'  => Base::$curr_date_time,//修改时间
                'original_id'  => $originalId,
                'source'       => $source,
                'cites_counter'=> $cites,
            );
            return $this->keywordsLogic->addKeywords($params,$_id);
        }

    }

}