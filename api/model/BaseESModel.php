<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2019/3/7
 * Time: 14:00
 */

namespace api\model;


class BaseESModel
{

    protected $_indexname;

    protected $_mappingname;

    protected $escliend = null;

    /**
     * KeywordsModel constructor.
     * @param $escliend \Elasticsearch\Client
     */
    public function __construct($escliend)
    {
        $this->escliend = $escliend;
    }

    /**
     * 根据id 批量获取文档信息
     * @param array $arrId
     * @return array
     */
    public function mgetById($arrId)
    {
        $params = [
            'index' =>$this->_indexname,
            'type'  =>$this->_mappingname,
            'body'  => [
                'ids'=>$arrId,
            ],
            'client'=>[
                'ignore'=>'404'
            ],
        ];
        $result = $this->escliend->mget($params);
        if(isset($result['docs']) && $result['docs']) {
            $list = [];
            foreach ($result['docs'] as $item) {
                if(isset($item['_source'])) {
                    $list[$item['_id']] = $item['_source'];
                }
            }
            return ['ret'=>0,'reason'=>'','data'=>$list];
        }
        return ['ret'=>1,'reason'=>'get failed','data'=>[]];
    }

    /**
     * 批量更新
     * @param $params = [
     * 'doc id'=>[更新数据键值对...]
     * ]
     * @return array
     */
    public function updateByBulk($params)
    {
        $editDoc = [];
        foreach ($params as $key => $item){
            $editDoc['body'][] = [
                'update' => [
                    '_index' => $this->_indexname,
                    '_type' => $this->_mappingname,
                    '_id' => $key
                ]
            ];
            $editDoc['body'][] = [
                'doc'=>$item
            ];
        }
        $result = $this->escliend->bulk($editDoc);
        if(is_array($result['items'])) {
            $list = [];
            foreach ($result['items'] as $value) {
                if(isset($value['update']['result'])) {
                    $list[$value['update']['_id']] = $value['update']['result'];
                } else {
                    $list[$value['update']['_id']] = 0;
                }

            }
            return ['ret'=>0,'data'=>$list];
        }
        return ['ret'=>1,'reason'=>'bulk failed'];
    }
}