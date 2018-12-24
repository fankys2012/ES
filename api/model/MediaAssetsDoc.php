<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2018/12/21
 * Time: 11:46
 */

namespace api\model;


class MediaAssetsDoc
{
    const INDEXNAME = 'mediaassets';
    const MAPPINGNAME = 'ma_mapping';

    protected $escliend = null;

    /**
     * KeywordsModel constructor.
     * @param $escliend \Elasticsearch\Client
     */
    public function __construct($escliend)
    {
        $this->escliend = $escliend;
    }

    public function createIndex()
    {
        $params = [
            'index' => self::INDEXNAME,
            'body' => [
                'settings' => [
                    'number_of_shards' => 3,
                    'number_of_replicas' => 2,
                    'analysis'=>[
                        'analyzer'=>[
                            'ik_pinyin_analyzer'=>[
                                'type'=>'custom',
                                'tokenizer'=>'ik_smart',
                                'filter'=>['kw_pinyin','word_delimiter']
                            ],
                            'pinyin_analyzer'=>[
                                'type'=>'custom',
                                'tokenizer'=>'separate_pinyin',
                                'filter'=>['kw_pinyin']
                            ]
                        ],
                        'filter'=>[
                            'kw_pinyin'=>[
                                'type'=>'pinyin',
                                'first_letter'=>'prefix',
                                'padding_char'=>' '
                            ]
                        ],
                        'tokenizer'=>[
                            'separate_pinyin'=>[
                                'type'=>'pinyin',
                                'keep_full_pinyin'=>false,
//                                'keep_separate_first_letter'=>true,//刘德华>l,d,h default: false
                                'keep_joined_full_pinyin'=>true,
                                'lowercase'=>true
                            ]
                        ]
                    ]
                ],

            ],
            'client'=>[
                'ignore'=>'404'
            ],
        ];
        $result = $this->escliend->indices()->create($params);
        if(empty($result))
        {
            return ['ret'=>1,'reason'=>'create index failed'];
        }
        return ['ret'=>0,'reason'=>'success'];
    }

    public function createMapping()
    {
        $params = [
            'index'=> self::INDEXNAME,
            'type' => self::MAPPINGNAME,
            'body' => [
                self::MAPPINGNAME=>[
                    'properties'=>[
                        'name'=>[
                            'type'=>"text",
                            'analyzer'=>'ik_max_word',
                            'fields'=>[
                                'pinyin'=>[
                                    'type'=>'text',
                                    'term_vector'=>'with_positions_offsets',
                                    'analyzer'=>'pinyin_analyzer',
                                ]
                            ]
                        ],
                        'alias_name'=>[
                            'type'=>"text",
                            'fields'=>[
                                'pinyin'=>[
                                    'type'=>'text',
                                    'term_vector'=>'with_positions_offsets',
                                    'analyzer'=>'pinyin_analyzer',
                                ]
                            ]
                        ],
                        'summary'=>[
                            'type'=>'text',
                            'analyzer'=>'ik_max_word',
                        ],
                        'director'=>[
                            'properties'=>[
                                'name'=>[
                                    'type'=>'text',
                                    'analyzer'=>'ik_max_word',
                                    'fields'=>[
                                        'pinyin'=>[
                                            'type'=>'text',
                                            'term_vector'=>'with_positions_offsets',
                                            'analyzer'=>'pinyin_analyzer',
                                        ]
                                    ]
                                ],
                                'id'=>[
                                    'type'=>'keyword',
                                ],
                            ]
                        ],
                        'actor'=>[
                            'properties'=>[
                                'name'=>[
                                    'type'=>'text',
                                    'analyzer'=>'ik_max_word',
                                    'fields'=>[
                                        'pinyin'=>[
                                            'type'=>'text',
                                            'term_vector'=>'with_positions_offsets',
                                            'analyzer'=>'pinyin_analyzer',
                                        ]
                                    ]
                                ],
                                'id'=>[
                                    'type'=>'keyword',
                                ],
                            ]
                        ],
                        //上线媒资包栏目
                        'package'=>[
                            'properties'=>[
                                'asset_id'=>[
                                    'type'=>'text'
                                ],
                                'cate_id'=>[
                                    'type'=>'text'
                                ],
                            ],
                        ],
                        //影片类型 nns_view_type [电影、综艺....]
                        'asset_type'   => ['type'=>'keyword'],

                        'category'     => ['type'=>'keyword'],//分类 vod:点播媒资，special：专题
                        'weight'       => ['type'=>'long'],//权重
                        't_click'      => ['type'=>'long'],//总点击数
                        'state'        => ['type'=>'short'],//状态 1：启用 0：禁用
                        'oned_click'   => ['type'=>'long'],//1日点击量
                        'sd_click'     => ['type'=>'long'],//7日点击量
                        'sd_avg_click' => ['type'=>'long'],//7日日均点击量
                        'fth_click'    => ['type'=>'long'],//15日点击量
                        'fth_agv_click'=>['type'=>'long'],//15日日均点击量
                        'm_click'      =>['type'=>'long'],//30日点击量
                        'm_agv_click'  =>['type'=>'long'],//30日日均点击量
                        'create_time'  =>['type'=>'keyword'], //创建时间
                        'modify_time'  =>['type'=>'keyword'],//修改时间
                        'original_id'  =>['type'=>'keyword'],//原始ID
                        'source'       =>['type'=>'keyword'],//数据来源
                    ]
                ]
            ],
            'client'=>[
                'ignore'=>'404'
            ],
        ];
        $result = $this->escliend->indices()->putMapping($params);
        if(empty($result))
        {
            return ['ret'=>1,'reason'=>'create index failed'];
        }
        return ['ret'=>0,'reason'=>'success'];
    }

    /**
     * 添加文档
     * @param $params
     * @param null $id
     * @return array
     */
    public function addDoc($params,$id = null)
    {
        $params = [
            'index' => self::INDEXNAME,
            'type' => self::MAPPINGNAME,
            'body' => $params,
            'client'=>[
                'ignore'=>'404'
            ],
        ];
        if($id) {
            $params['id'] = $id;
        }
        $result = $this->escliend->index($params);
        if($result && isset($result['_id']) && $result['_id']) {
            return ['ret'=>0,'reason'=>'success','data'=>['id'=>$result['_id']]];
        }
        return ['ret'=>0,'reason'=>'add keywords failed'];
    }

    /**
     * 局部更新文档
     * @param array  $params
     * @param string $id
     * @return array
     */
    public function editDoc($params,$id)
    {
        $params = [
            'index' => self::INDEXNAME,
            'type' => self::MAPPINGNAME,
            'id'   => $id,
            'body' => [
                'doc'=>$params
            ],
            'client'=>[
                'ignore'=>'404'
            ],
        ];
        $result = $this->escliend->update($params);
        if($result && isset($result['_id']) && $result['_id']) {
            return ['ret'=>0,'reason'=>'success'];
        }
        return ['ret'=>0,'reason'=>'edit keywords failed'];
    }

    /**
     * get doc by doc id
     * @param string $id
     * @return array
     */
    public function getDocById($id)
    {
        $params = [
            'index' =>self::INDEXNAME,
            'type'  =>self::MAPPINGNAME,
            'id'=>$id,
            'client'=>[
                'ignore'=>'404'
            ],
        ];
        $result = $this->escliend->get($params);

        if(empty($result) || $result['found'] == false) {
            return ['ret'=>1,'reason'=>'not found'];
        }
        $data = $result['_source'];
        $data['_id'] = $result['_id'];
        return ['ret'=>0,'reason'=>'','data'=>$data];
    }

    /**
     * delete doc by doc id
     * @param string $id doc id
     * @return ['ret'=>0/1,'reson'=>'xxx']
     */
    public function delDocById($id)
    {
        $params = [
            'index' =>self::INDEXNAME,
            'type'  =>self::MAPPINGNAME,
            'id'=>$id,
            'client'=>[
                'ignore'=>'404'
            ],
        ];
        $result = $this->escliend->delete($params);

        if(empty($result) || $result['result'] != 'deleted' ) {
            return ['ret'=>1,'reason'=>'not found'];
        }

        return ['ret'=>0,'reason'=>'success'];
    }
}