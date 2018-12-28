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
                'should'=>[
                    [
                        'prefix'=>[
                            'name.pinyin'=>strtolower($name)
                        ]
                    ],
                    [
                        'prefix'=>[
                            'alias_name.pinyin'=>strtolower($name)
                        ]
                    ],
                    [
                        'prefix'=>[
                            'director.name.pinyin'=>strtolower($name)
                        ]
                    ],
                    [
                        'prefix'=>[
                            'actor.name.pinyin'=>strtolower($name)
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
                    ],
                    ['match_phrase'=> [
                        'director.name'=>$name
                    ]
                    ],
                    ['match_phrase'=> [
                        'actor.name'=>$name
                    ]
                    ],
                    ['match_phrase_prefix'=> [
                        'alias_name'=>$name
                    ]
                    ],
                    ['match_phrase_prefix'=> [
                        'summary'=>$name
                    ]
                    ],
                ]
            ];

        }
        return $bool;
    }

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
}