<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2018/12/3
 * Time: 15:26
 */

namespace api\controllers;


use api\logic\ESLogic;
use frame\web\Controller;

class PinyinController extends Controller
{

    //@var \Elasticsearch\Client
    private $_esclient = null;

    public function __construct()
    {
        $this->_esclient = ESLogic::getInstance()->connect();
    }

    public function createIndexAction()
    {
        $params = [
            'index' => 'test',
            'body' => [
                'settings' => [
                    'number_of_shards' =>  5,      // 分片 默认5
                    'number_of_replicas' =>1   // 副本、备份 默认1
                ]
            ],
            'client' => [
                'verbose' => true
            ]
        ];
        $result = $this->_esclient->indices()->create($params);
        echo "<pre>";
        var_dump($result);
        print_r($result);
    }

    /**
     * 更改或增加索引的映射
     */
    public function putMappingsAction()
    {
        $params = [
            'index' => 'test',
            'type' => 'pinyin',
            'body' => [
                'pinyin' => [
                    '_source' => [
                        'enabled' => true
                    ],
                    'properties' => [
                        'name' => [                     // name 是需要搜索分词的字段
                            'type' => 'text',
                            'analyzer' => 'ik_smart',
                            'search_analyzer' => 'ik_smart',
                            'search_quote_analyzer' => 'ik_smart'
                        ]
                    ]
                ]
            ]
        ];
        $result = $this->_esclient->indices()->putMapping($params);
        var_dump($result);
    }

    public function getSettingsAction()
    {
        $params = ['index' => 'test'];
        $result = $this->_esclient->indices()->getSettings($params);
        echo "<pre>";
        print_r($result);
    }

    public function getMappingsAction()
    {
        $params = ['index' => 'test'];
        $result = $this->_esclient->indices()->getMapping($params);
        echo "<pre>";
        print_r($result);
    }
    public function getDocAction()
    {
        $params = [
            'index' => 'test',
            'type' => 'pinyin',
            'body'=>[],
        ];
        $result = $this->_esclient->index($params);
        echo "<pre>";
        print_r($result);

    }

    /**
     * 数据插入
     */
    public function insertDocAction()
    {
        $params = [
            'index' => 'test',
            'type' => 'pinyin',
            'id' => '10086',
            'body' => [
                'name' =>'刘德华'
            ]
        ];
        $result = $this->_esclient->index($params);
        echo "<pre>";
        print_r($result);
    }

    /**
     * 批量插入
     */
    public function bulkInsertAction()
    {
        $data = [
            [
                'id' => '1000',
                'name'=>'詹子瑜',
            ],
            [
                'id' => '1001',
                'name'=>'天龙八部[87版]',
            ],
        ];
        foreach($data as $value){
            $params['body'][] = [
                'index' => [
                    '_index' => 'test',
                    '_type' => 'pinyin',
                    '_id'  =>$value['id']
                ]
            ];
            $params['body'][] = [
                'name' => $value['name'],
            ];
        }
        $result = $this->_esclient->bulk($params);
        echo "<pre>";
        print_r($result);


    }

    public function getSingelAction()
    {
        $params = [
            'index' => 'test',
            'type' => 'pinyin',
            'id' => '1000'
        ];
        $result = $this->_esclient->get($params);
        echo "<pre>";
        print_r($result);
    }

    public function searchAction()
    {
        $params = [
            'index' =>  'test',   //['my_index1', 'my_index2'],可以通过这种形式进行跨库查询
            'type' => 'pinyin',//['my_type1', 'my_type2'],
            'body' => [
                'query' => [
                    'match'=>[
                        'name'=>'',
                    ]

                ],
//                'from' => '0',  // 分页
//                'size' => '200',  // 每页数量
//                'sort' => [  // 排序
//                    'age' => 'desc'   //对age字段进行降序排序
//                ]
            ]
        ];
        $result = $this->_esclient->search($params);
        echo "<pre>";
        print_r($result);

    }

    public function updateDocAction()
    {
        $params = [
            'index' => 'my_index',
            'type' => 'my_type',
            'id' => '10086',
            'body' => [
                'doc' => [  // 必须带上这个.表示是文档操作
                    'name' => 'abc'
                ]
            ]
        ];


    }


    public function createAction()
    {
        $params = [
            'index'=>'test',
            'type' =>'pinyin',
            'body'=>[
                'pinyin' =>[
                    '_source'=>[
                        'enabled'=>true,
                    ],
//                    'properties' => [
//                        'title'=> [
//                            'type'=>'text',
//                            'analyzer'=>'ik_smart',
//
//                        ]
//                    ],
                    'analysis'=>[
                        'analyzer'=>[
                            'pinyin_analyzer'=>[
                                'tokenizer'=>'my_pinyin'
                            ]
                        ],
                        'tokenizer'=>[
                            'my_pinyin'=>[
                                'type'=>'pinyin',
                                "keep_separate_first_letter" => false,
                                "keep_full_pinyin" => true,
                                "keep_original" => true,
                                "limit_first_letter_length" => 16,
                                "lowercase" => true,
                                "remove_duplicated_term" => true
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $result = $this->_esclient->indices()->create($params);
        var_dump($result);

    }



    public function t1Action()
    {
        $params = [
            'index' => 'test',
//            'id'=>'my_type',
            'body' => [
                'settings' => [
                    'number_of_shards' => 3,
                    'number_of_replicas' => 2
                ],
                'mappings' => [
                    'my_type' => [
                        '_source' => [
                            'enabled' => true
                        ],
                        'properties' => [
                            'first_name' => [
                                'type' => 'keyword',
//                                'analyzer' => 'standard'
                            ],
                            'age' => [
                                'type' => 'integer'
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $result = $this->_esclient->indices()->create($params);

        var_dump($result);
    }

    public function deleteIndexAction()
    {
        $params = [
            'index' => 'test',
        ];
        $result = $this->_esclient->indices()->delete($params);

        var_dump($result);
    }
}