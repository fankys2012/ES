<?php
/**
 * 普通脚本非定时器
 * User: fankys
 * Date: 2019/1/8
 * Time: 11:45
 */

namespace api\timer;


use api\logic\ESLogic;
use api\logic\MediaAssetsLogic;
use api\model\KeywordsModel;
use api\model\MediaAssetsDoc;
use frame\console\Controller;

class InitDocController extends Controller
{

    public function initKeywordsAction()
    {

        $esclient = ESLogic::getInstance()->connect();
        $kyModel = new KeywordsModel($esclient);
        //文档是否存在
        $params = [
            'index'=>KeywordsModel::INDEXNAME,
        ];
        $existsIndex = $esclient->indices()->exists($params);
        if($existsIndex == false) {
            $create_res = $kyModel->CreateIndex();
            if($create_res['ret'] == 1) {
                echo $create_res['reason'];
                return ;
            }
        }
        //创建更新文档
        $mapres = $kyModel->CreateMapping();
        if($mapres['ret'] ==0) {
            echo "关键词文档初始化成功\n";
        }
        else {
            echo "关键词文档初始化失败\n";
        }
        return;
    }

    public function initMediaAssetsAction()
    {

        $esclient = ESLogic::getInstance()->connect();
        $mediaAssetsLogic = new MediaAssetsLogic();
        //文档是否存在
        $params = [
            'index'=>MediaAssetsDoc::INDEXNAME,
        ];
        $existsIndex = $esclient->indices()->exists($params);
        if($existsIndex == false) {
            $create_res = $mediaAssetsLogic->mediaAsstesDocModel->createIndex();
            if($create_res['ret'] == 1) {
                echo $create_res['reason'];
                return ;
            }
        }
        //创建更新文档
        $mapres = $mediaAssetsLogic->mediaAsstesDocModel->createMapping();
        if($mapres['ret'] ==0) {
            echo "媒资文档初始化成功\n";
        }
        else {
            echo "媒资文档初始化失败\n";
        }
        return;
    }

    public function pinyinAction()
    {
        $esclient = ESLogic::getInstance()->connect();
        $existsIndex = $esclient->indices()->exists(['index'=>'pinyin']);
        if($existsIndex == true) {
            $del = $esclient->indices()->delete(['index'=>'pinyin']);
        }

        $params = [
            'index' => 'pinyin',
            'body' => [
                'settings' => [
                    'number_of_shards' => 3,
                    'number_of_replicas' => 2,
                    'analysis'=>[
                        'filter'=>[
                            'separate_pinyin'=>[
                                'type'=>'pinyin',
                                'keep_first_letter'=>true,
                                'keep_full_pinyin'=>false,
                                'keep_joined_full_pinyin'=>true,
                                'keep_none_chinese_in_joined_full_pinyin'=>false,
                                'none_chinese_pinyin_tokenize'=>false,
                                'lowercase'=>true,
                            ]
                        ],
                        'analyzer'=>[
                            'ik_pinyin_analyzer'=>[
                                'type'=>'custom',
                                'tokenizer'=>'whitespace',
                                'filter'=>['separate_pinyin']
                            ],
                        ],
                    ]
                ],

            ]
        ];
        $result = $esclient->indices()->create($params);
        var_dump($result);
    }
}