<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2018/12/12
 * Time: 14:29
 */

namespace api\model;


use frame\Base;

class KeywordsModel
{
    //var \Elasticsearch\Client
    protected $escliend = null;

    const INDEXNAME = 'keywords';

    const MAPPINGNAME = 'kw_mapping';

    /**
     * KeywordsModel constructor.
     * @param $escliend \Elasticsearch\Client
     */
    public function __construct($escliend)
    {
        $this->escliend = $escliend;
    }

    public function CreateIndex()
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
                                'tokenizer'=>'separate_pinyin'
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

            ]
        ];
        $result = $this->escliend->indices()->create($params);
        if(empty($result))
        {
            return ['ret'=>1,'reason'=>'create index failed'];
        }
        return ['ret'=>0,'reason'=>'success'];
    }

    public function CreateMapping()
    {
        $params = [
            'index'=> self::INDEXNAME,
            'type' => self::MAPPINGNAME,
            'body' => [
                self::MAPPINGNAME=>[
                    'properties'=>[
                        'name'=>[
                            'type'=>"text",
                            'fields'=>[
                                'pinyin'=>[
                                    'type'=>'text',
                                    'term_vector'=>'with_positions_offsets',
                                    'analyzer'=>'pinyin_analyzer',
//                                    "boost"=>10
                                ]
                            ]
                        ],
                        'category'     => ['type'=>'keyword'],
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
                        'cites_counter'=>['type'=>'long'],//关键词引用总数

                        'cites_data'=>[
                            'properties'=>[
                                'original_id'=>[
                                    'type'=>'keyword'
                                ],
                                //关键词引用的cp
                                'cp_id'=>[
                                    'type'=>'keyword'
                                ],
                                'epg_tag'=>[
                                    'type'=>'keyword'
                                ]
                            ],
                        ],
                    ]
                ]

            ],
        ];
        $result = $this->escliend->indices()->putMapping($params);
        if(empty($result))
        {
            return ['ret'=>1,'reason'=>'create index failed'];
        }
        return ['ret'=>0,'reason'=>'success'];
    }

    public function getMapping()
    {
        $params = [
            'index' => self::INDEXNAME,

        ];
        $res = $this->escliend->indices()->getMapping($params);
        return $res;
    }

    /**
     * 删除所有数据 -- 测试使用 上线后禁用
     */
    public function deleteAll()
    {
        $params = [
            'index' =>self::INDEXNAME,
            'type'  =>self::MAPPINGNAME,
            'body'=>[
                "query"=>[
                    'match_all'=>new \stdClass(),
                ],
            ],

            'client'=>[
                'ignore'=>'404'
            ],
        ];
        $this->escliend->deleteByQuery($params);
    }

    /**
     * 添加关键词文档字段
     * @param string $name      关键词名称
     * @param string $category   分类
     * @param int    $state        状态
     * @param string $originalId 原始id
     * @param string $source
     * @param int $cites         引用数
     * @return array
     */
    public static function getAddFieldData($name,$category,$state=1,$originalId,$source='cms',$cites=0)
    {
        $params = array(
            'name'         => $name,
            'category'     => [$category],
            'weight'       => 1,//权重
            'state'        => $state,//状态 1：启用 0：禁用
            't_click'      => 1,//总点击数
            'oned_click'   => 1,//1日点击量
            'sd_click'     => 1,//7日点击量
            'sd_avg_click' => 1,//7日日均点击量
            'fth_click'    => 1,//15日点击量
            'fth_agv_click'=> 1,//15日日均点击量
            'm_click'      => 1,//30日点击量
            'm_agv_click'  => 1,//30日日均点击量
            'create_time'  => Base::$curr_date_time, //创建时间
            'modify_time'  => Base::$curr_date_time,//修改时间
            'original_id'  => $originalId,
            'source'       => $source,
            'cites_counter'=> $cites,
        );
        return $params;
    }

    /**
     * 获取更新点击数字段信息
     * @param $params
     * @return array
     */
    public static function getClickFieldData(&$params)
    {
        $fieldData = [];
        $allowFields = ['weight','t_click','oned_click','sd_click','sd_avg_click','fth_click','fth_agv_click','m_click','m_agv_click'];
        foreach ($allowFields as $key) {
            if(isset($params[$key])) {
                $fieldData[$key] = intval($params[$key]);
            }
        }
        return $fieldData;
    }

}