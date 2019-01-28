<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2018/12/12
 * Time: 17:16
 */

namespace api\logic;


use api\model\KeywordsModel;
use api\model\MediaAssetsDoc;
use frame\helpers\BaseVarDumper;
use frame\Log;

class KeywordsLogic
{
    //@var
    public $esclient=null;

    public function __construct()
    {
        $this->esclient = ESLogic::getInstance();
    }


    /**
     * 添加关键词
     * @param $params
     * @param null $id
     * @return array
     */
    public function addKeywords($params,$id = null)
    {
        $params = [
            'index' => KeywordsModel::INDEXNAME,
            'type' => KeywordsModel::MAPPINGNAME,
            'body' => $params,
            'client'=>[
                'ignore'=>'404'
            ],
        ];
        if($id) {
            $params['id'] = $id;
        }
        $client = $this->esclient->connect();
        $result = $client->index($params);
        if($result && isset($result['_id']) && $result['_id']) {
            return ['ret'=>0,'reason'=>'success','data'=>['id'=>$result['_id']]];
        }
        return ['ret'=>0,'reason'=>'add keywords failed'];
    }

    /**
     * @param $id
     * @return array
     */
    public function getById($id)
    {
        $params = [
            'index' =>KeywordsModel::INDEXNAME,
            'type'  =>KeywordsModel::MAPPINGNAME,
            'id'=>$id,
            'client'=>[
                'ignore'=>'404'
            ],
        ];
        $client = $this->esclient->connect();
        $result = $client->get($params);

        if(empty($result) || $result['found'] == false) {
            return ['ret'=>1,'reason'=>'not found'];
        }
        $data = $result['_source'];
        $data['_id'] = $result['_id'];
        return ['ret'=>0,'reason'=>'','data'=>$data];
    }

    public function getList($query,$from=0,$size=12)
    {
        $params = [
            'index' =>KeywordsModel::INDEXNAME,
            'type'  =>KeywordsModel::MAPPINGNAME,
            'size'  =>$size,
            'from'  =>$from,
            'body'  => $query,
            'client'=>[
                'ignore'=>'404'
            ],
        ];
        $client = $this->esclient->connect();
        $result = $client->search($params);
        if($result['hits'])
        {
            $list = [];
            foreach ($result['hits']['hits'] as $item)
            {
                $data = $item['_source'];
                $data['id'] = $item['_id'];
                if(isset($item['highlight']['name'])) {
                    $data['highlight']['name'] = $item['highlight']['name'];
                }
                elseif (isset($item['highlight']['name.pinyin'])) {
                    $data['highlight']['name'] = $item['highlight']['name.pinyin'];
                }
                $list[] = $data;
                unset($data);
            }
            return [
                'ret'=>0,
                'reason'=>'success',
                'data'=>[
                    'total'=>$result['hits']['total'],
                    'list'=>$list
                ]
            ];
        }
        return ['ret'=>1,'reason'=>'not found'];

    }

    public function delKeywordById($id)
    {
        $params = [
            'index' =>KeywordsModel::INDEXNAME,
            'type'  =>KeywordsModel::MAPPINGNAME,
            'id'    =>$id,
            'client'=>[
                'ignore'=>'404'
            ],
        ];
        $client = $this->esclient->connect();
        $result = $client->delete($params);

        if(empty($result) || $result['result'] != 'deleted' ) {
            return ['ret'=>1,'reason'=>'not found'];
        }

        return ['ret'=>0,'reason'=>''];
    }

    public function updateKeywords($id,$params)
    {
        $params = [
            'index' => KeywordsModel::INDEXNAME,
            'type' => KeywordsModel::MAPPINGNAME,
            'id'   => $id,
            'body' => [
                'doc'=>$params
            ],
            'client'=>[
                'ignore'=>'404'
            ],
        ];

        $client = $this->esclient->connect();
        $result = $client->update($params);
        if($result && isset($result['_id']) && $result['_id']) {
            return ['ret'=>0,'reason'=>'success'];
        }
        return ['ret'=>0,'reason'=>'edit keywords failed'];
    }

    public function refresh()
    {
        $params = [
            'index' => KeywordsModel::INDEXNAME,
            'client'=>[
                'ignore'=>'404'
            ],
        ];
        $client = $this->esclient->connect();
        $client->indices()->refresh($params);
        return ['ret'=>0,'reason'=>'success'];
    }

    /**
     * 批量插入关键词
     * @param array $arrData
     * @return array
     */
    public function batchUpdate($arrData=[])
    {
        $params = ['body' => []];
        $arr_msg_id = [];
        foreach ($arrData as $item) {
            $params['body'][] = [
                'index' => [
                    '_index' => KeywordsModel::INDEXNAME,
                    '_type' => KeywordsModel::MAPPINGNAME,
                    '_id' => $item['_id']
                ]
            ];
            $arr_msg_id[$item['msg_id']] = $item['_id'];
            unset($item['_id'],$item['msg_id']);
            $params['body'][] = $item;
        }

        $client = $this->esclient->connect();
        $result = $client->bulk($params);
        $success_item = [];
        if(is_array($result['items'])) {
            foreach ($result['items'] as $value) {
                $success_item[$value['index']['_id']] = $value['index']['result'];
            }
        }

        $returnList = [
            'success'=>[],
            'failed' =>[],
        ];
        foreach ($arr_msg_id as $key =>$value)
        {
            if(isset($success_item[$value])) {
                $returnList['success'][] = ['id'=>$key,'msg'=>'success'];
            }
            else {
                $returnList['failed'][] = ['id'=>$key,'msg'=>'failed'];
            }
        }

        return ['ret'=>0,'data'=>$returnList];
    }


    /**
     * 更新关键词点击数
     * @param array $params
     * @return array
     */
    public function updateClick(&$params)
    {
        //类型
        $category = $params['category'] ?: 'vod';
        $original_id = $params['original_id'];
        $soruce = $params['soruce'] ?: 'cms';

        if($category == 'star') {
            if(empty($original_id)) {
                return ['ret'=>1,'reason'=>'original_id can not empty'];
            }
            $id = md5($original_id.$soruce);
        }
        else {
            $id = md5($params['name']);
        }

        $exist = $this->getById($id);
        if($exist['ret'] == 0) {
            $fieldsData = KeywordsModel::getClickFieldData($params);

            $result = $this->updateKeywords($id,$fieldsData);

            return $result;
        }
        else {
            return ['ret'=>1,'reason'=>'keywords not found'];
        }
    }

    /**
     * 更新关键词媒资引用数
     * @param string $_id 关键词文档id
     * @return array
     */
    public function updateMediaCites($_id)
    {
        static $mediaAssetsDocModel = null;
        if($mediaAssetsDocModel == null) {
            $mediaAssetsDocModel = new MediaAssetsDoc($this->esclient->connect());
        }

        $query = [
            '_source'=>[
                'includes'=>['name','original_id','state','package','cp_id','epg_tag']
            ],
            'query'=>[
                'bool'=>[
                    'filter'=>[
                        'bool'=>[
                            'must'=>[
                                ['term'=>[
                                    'kw_cites'=>$_id
                                ]],
                                ['term'=>[
                                    'state'=>1
                                ]],
                            ]
                        ]
                    ]
                ]
            ]
        ];
        Log::info("更新关键词[{$_id}]引用信息");
        $result = $mediaAssetsDocModel->getList($query,0,1000);
        if($result['ret'] == 0) {
            $total = $result['data']['total'];
            $package = [];
            $cp_id = [];
            $epg_tag = [];
            if(is_array($result['data']['list'])) {
                foreach ($result['data']['list'] as $item) {
                    if(is_array($item['package'])) {
                        foreach ($item['package'] as $value) {
                            $package[] = $value['id'];
                        }
                    }
                    if(isset($item['cp_id']) && $item['cp_id']) {
                        $cp_id[] = $item['cp_id'];
                    }
                    if(is_array($item['epg_tag'])) {
                        $epg_tag = array_merge($epg_tag,$item['epg_tag']);
                    } else {
                        $epg_tag[] = $item['epg_tag'];
                    }
                }
                $package = array_unique($package);
                $cp_id = array_unique($cp_id);
                $epg_tag = array_unique($epg_tag);
            }
            $packageId = [];
            foreach ($package as $value) {
                $packageId[] = ['id'=>$value];
            }
            $params = [
                'cites_counter'=>$total,
                'package'=>$packageId,
                'cp_id'=>$cp_id,
                'epg_tag'=>$epg_tag,
            ];
            Log::info("关键词[{$_id}]更新引用：".BaseVarDumper::export($params));
            $edres = $this->updateKeywords($_id,$params);
            if($edres['ret'] !=0 ) {
                Log::error("关键词[{$_id}]更新引用失败");
            }

            return $edres;
        }
        return $result;
    }
}