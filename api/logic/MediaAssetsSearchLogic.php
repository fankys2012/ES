<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2018/12/26
 * Time: 17:12
 */

namespace api\logic;


class MediaAssetsSearchLogic
{
    /**
     * 查询socre
     * @param string $factorType 类型
     * @param string $kind       search/keywords
     * @return array
     */
    public static function funcScore($factorType=null,$kind='search')
    {
        $conf = require APP_DIR.'/config/weight.php';
        $factorType = $factorType ?: 'vod';
        if(isset($conf['search'][$factorType])) {
            $conf = $conf[$kind][$factorType];
        }
        else {
            $conf = [];
        }
        /*
         * modifier 定义
         *   none：不处理
         *   log：计算对数
         *   log1p：先将字段值 +1，再计算对数
         *   log2p：先将字段值 +2，再计算对数
         *   ln：计算自然对数
         *   ln1p：先将字段值 +1，再计算自然对数
         *   ln2p：先将字段值 +2，再计算自然对数
         *   square：计算平方
         *   sqrt：计算平方根
         *   reciprocal：计算倒数
         */
        $arr_fields = [
            'oned_click'=>[
                'field'=>'oned_click',
                'modifier'=>'log1p'
            ],
            'sd_click'=>[
                'field'=>'sd_click',
                'modifier'=>'log1p'
            ],
            'sd_avg_click'=>[
                'field'=>'sd_avg_click',
                'modifier'=>'log1p'
            ],
            'fth_click'=>[
                'field'=>'fth_click',
                'modifier'=>'log1p'
            ],
            'fth_agv_click'=>[
                'field'=>'fth_agv_click',
                'modifier'=>'log1p'
            ],
            'field_value_factor'=>[
                'field'=>'field_value_factor',
                'modifier'=>'log1p'
            ],
            'm_agv_click'=>[
                'field'=>'m_agv_click',
                'modifier'=>'log1p'
            ],
            'weight'=>[
                'field'=>'weight',
                'modifier'=>'reciprocal'
            ]
        ];
        $score = [];
        foreach ($arr_fields as $key => $item)
        {
            if(isset($conf[$key]) && $conf[$key] != '0')
            $score[] = [
                'field_value_factor'=>[
                    'field'=>$item['field'],//15日点击量
                    'modifier'=>$item['modifier'],
                    'factor'=>$conf[$key],
                ]
            ];
        }
        return $score;
    }

    /**
     * @param $name
     * @return array
     */
    public static function boolMatch($name)
    {
        if(empty($name)) {
            return [];
        }
        if(preg_match ("/^[A-Za-z]+$/u", $name)) {
            $bool = [
                'must'=>[
                    'multi_match'=>[
                        'query'=>$name,
                        //@see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-multi-match-query.html
                        'type'=>'phrase_prefix',
                        'fields'=>['name.pinyin','alias_name.pinyin','director.name.pinyin','actor.name.pinyin']
                    ]
                ]
            ];

        }
        else {
            $bool = [
                'must'=>[
                    'multi_match'=>[
                        'query'=>$name,
                        //@see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-multi-match-query.html
                        'type'=>'phrase_prefix',
                        'fields'=>['name','director.name','actor.name','alias_name','summary^0.5']
                    ]
                ]

            ];

        }
        return $bool;
    }

    /**
     * 搜索过滤器
     * @param $params
     * @return array
     */
    public static function boolFilter($params)
    {
        //分类过滤
        if(isset($params['category']) && $params['category']) {
            $bool['must'][] = [
                'term'=>[
                    'category'=>$params['category']
                ]
            ];
        }
        //状态过滤
        if(isset($params['state']) && $params['state']) {
            $bool['must'][] = [
                'term'=>[
                    'state'=>$params['state']
                ]
            ];
        }
        //媒资类型过滤
        if(isset($params['asset_type']) && $params['asset_type']) {
            $bool['must'][] = [
                'term'=>[
                    'asset_type'=>$params['asset_type']
                ]
            ];
        }
        //cp过滤
        if(isset($params['cp_id']) && strlen($params['cp_id'])>0) {
            $arr_cp_id = explode(',',$params['cp_id']);
            if(count($arr_cp_id) >1){
                $bool['must'][] = [
                    'terms'=>[
                        'cp_id'=>$arr_cp_id
                    ]
                ];
            }
            else {
                $bool['must'][] = [
                    'term'=>[
                        'cp_id'=>$params['cp_id']
                    ]
                ];
            }

        }
        /*
         * 键词搜索 引用必须大于0
         *  gt : greater than
         *  lt : less than
         *  gte: greater than or equal to
         *  lte: less than or equal to
         */
        if(isset($params['cites_counter'])) {
            $bool['must'][] = [
                'range'=>[
                    'cites_counter'=>[
                        'gt'=>0
                    ]
                ]
            ];
        }

        //EGP tag 过滤
//        if(isset($params['epg_tag']) && $params['epg_tag']) {
//            $bool['should'][] = [
//                'term'=>[
//                    'epg_tag'=>$params['epg_tag']
//                ]
//            ];
//        }

        return ['filter'=>[
            'bool'=>$bool,
        ]];
    }


    /**
     * 关键词查询
     * @param $name
     * @return array
     */
    public static function keywordsBoolMatch($name)
    {
        if(empty($name)) {
            return [];
        }
        if(preg_match ("/^[A-Za-z]+$/u", $name)) {
            $bool = [
                'should'=>[
                    [
                        'prefix'=>[
                            'name.pinyin'=>strtolower($name)
                        ]
                    ]
                ]
            ];

        }
        else {
            $bool = [
                'should'=> [
                    ['match_phrase_prefix'=> [
                        'name'=>$name
                    ]
                    ]
                ]
            ];
        }

        return $bool;
    }

    /**
     * 按给定字段聚合
     * @param array $aggFields  聚合字段
     * @return array ['aggfield'=>['terms'=>[...]]]
     */
    public static function assetsAggs($aggFields)
    {
        $aggs = [];
        if(is_array($aggFields)) {
            foreach ($aggFields as $item) {
                $aggs[$item] = [
                    'terms'=>[
                        'field'=>$item,
                    ]
                ];
            }
        }
        return $aggs;
    }
}