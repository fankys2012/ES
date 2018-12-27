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
     * @param null $factorType
     * @return array
     */
    public static function funcScore($factorType=null)
    {
        $conf = require APP_DIR.'/config/weight.php';
        $factorType = $factorType ?: 'vod';
        if(isset($conf['search'][$factorType])) {
            $conf = $conf['search'][$factorType];
        }
        else {
            $conf = [];
        }

        $score = [
            [
                'field_value_factor'=>[
                    'field'=>'oned_click',
                    'modifier'=>'log1p',//Add 1 to the field value and take the common logarithm
                    'factor'=>isset($conf['oned_click']) ? $conf['oned_click'] : 0,
                ]
            ],
            [
                'field_value_factor'=>[
                    'field'=>'sd_click',//7日点击量
                    'modifier'=>'log1p',
                    'factor'=>isset($conf['sd_click']) ? $conf['sd_click'] : 0,
                ]
            ],
            [
                'field_value_factor'=>[
                    'field'=>'sd_avg_click',//7日日均点击量
                    'modifier'=>'log1p',
                    'factor'=>isset($conf['sd_avg_click']) ? $conf['sd_avg_click'] : 0,
                ]
            ],
            [
                'field_value_factor'=>[
                    'field'=>'fth_click',//15日点击量
                    'modifier'=>'log1p',
                    'factor'=>isset($conf['fth_click']) ? $conf['fth_click'] : 0,
                ]
            ],
            [
                'field_value_factor'=>[
                    'field'=>'fth_agv_click',//15日日均点击量
                    'modifier'=>'log1p',
                    'factor'=>isset($conf['fth_agv_click']) ? $conf['fth_agv_click'] : 0,
                ]
            ],
            [
                'field_value_factor'=>[
                    'field'=>'m_click',//30日点击量
                    'modifier'=>'log1p',
                    'factor'=>isset($conf['m_click']) ? $conf['m_click'] : 0,
                ]
            ],
            [
                'field_value_factor'=>[
                    'field'=>'m_agv_click',//30日日均点击量
                    'modifier'=>'log1p',
                    'factor'=>isset($conf['m_agv_click']) ? $conf['m_agv_click'] : 0,
                ]
            ]
        ];
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
}