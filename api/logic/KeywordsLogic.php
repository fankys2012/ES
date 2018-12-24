<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2018/12/12
 * Time: 17:16
 */

namespace api\logic;


use api\model\KeywordsModel;

class KeywordsLogic
{
    //@var
    public $esclient=null;

    public function __construct()
    {
        $this->esclient = ESLogic::getInstance();
    }

    public function getByName($name)
    {
        $params = [
            'index' =>KeywordsModel::INDEXNAME,
            'type'  =>KeywordsModel::MAPPINGNAME,
            'body'=>[
                'query'=>[
//                    'bool'=>[
//
//                        'filter'=>[
                            'term'=>[
                                'name'=>$name
                            ]
//                        ]
//                    ],

                ]
            ]
        ];
        $client = $this->esclient->connect();
        $result = $client->search($params);

        return $result;
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

}