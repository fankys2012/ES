<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2018/12/21
 * Time: 14:11
 */

namespace api\controllers;


use api\logic\MediaAssetsLogic;
use api\logic\MediaAssetsSearchLogic;
use frame\Base;
use frame\web\Controller;

class MediaAssetsController extends Controller
{
    protected $mediaAssetsLogic = null;

    public function __construct($id, $module, array $config = [])
    {
        parent::__construct($id, $module, $config);

        $this->mediaAssetsLogic = new MediaAssetsLogic();
    }

    public function createAction()
    {
//        $index = $this->mediaAssetsLogic->mediaAsstesDocModel->createIndex();
//        if($index['ret'] ==1) {
//            return $this->reponse($index);
//        }
        $mapping = $this->mediaAssetsLogic->mediaAsstesDocModel->createMapping();
        return $this->reponse($mapping);
    }

    public function testAction()
    {

        $docList = [
            'name'=>'天龙八部',
            'alias_name'=>'',
            'summary'=>'故事以北宋末年动荡的社会环境为背景，展开波澜壮阔的历史画卷，塑造了乔峰、段誉、 虚竹、慕容复等形象鲜明的人物，成为武侠史上的经典之作。故事精彩纷呈，人物命运悲壮多变，是可读性很强的作品，具有震撼人心的力量',
            'director'=>[
                [
                    'name'=>'周晓文',
                    'id'=>'10001'
                ],
                [
                    'name'=>'胡军',
                    'id'=>'10002'
                ],
            ],
            'category'=>'vod'
        ];
        $result = $this->mediaAssetsLogic->mediaAsstesDocModel->addDoc($docList,'10001');
        return $this->reponse($result);

    }

    /**
     * recive store data
     */
    public function reciveAction()
    {
        $post_data = Base::$app->request->getParam('post_data');
        if(empty($post_data) || count($post_data) < 1)
        {
            return $this->reponse(['ret'=>1,'reason'=>'post_data is empty']);
        }
        $resultList = [];
        foreach ($post_data as $item)
        {
            $res = null;
            if($item['category'] == 'vod')
            {
                if($item['operate'] == 'update')
                {
                    unset($item['operate']);
                    $res = $this->mediaAssetsLogic->updateMediaAssetDoc($item);
                }
                elseif($item['operate'] == 'package')
                {
                    $_id = md5($item['original_id'].$item['source']);
                    $params = [
                        'package'=>$item['package'],
                        'state'=> count($item['package']) < 1 ? 0: 1,
                    ];
                    $res = $this->mediaAssetsLogic->mediaAsstesDocModel->editDoc($params,$_id);
                }
                elseif ($item['operate'] == 'delete')
                {
                    $_id = md5($item['original_id'].$item['source']);
                    $res = $this->mediaAssetsLogic->mediaAsstesDocModel->delDocById($_id);
                }
            }
            $res['id'] = $item['msg_id'];
            $resultList[] = $res;
        }
        return $this->reponse(['ret'=>0,'reason'=>'success','data'=>$resultList]);
    }

    public function delAllAction()
    {
        $this->mediaAssetsLogic->mediaAsstesDocModel->deleteAll();
        return $this->reponse(['ret'=>0,'reason'=>'success']);
    }

    public function searchAction()
    {
        $category = Base::$app->request->getParam('category','vod');
        $name = Base::$app->request->getParam('name');
        $from = Base::$app->request->getParam('from',0);
        $size = Base::$app->request->getParam('size',12);

        $score_func = MediaAssetsSearchLogic::funcScore($category);
        $query = [
            'query'=>[
                'bool'=>[

                ],
                'functions'=>$score_func,
                'boost_mode'=>'sum',//multiply,replace,sum,avg,max,min
                'score_mode'=>'sum',//multiply,sum,avg,first,max,min

            ],
        ];
        if($name) {
            if(preg_match ("/^[A-Za-z]+$/u", $name)) {
                $query['query']['bool']['must'][] = [
                    'prefix'=>[
                        'name.pinyin'=>strtolower($name)
                    ]
                ];
            }
            else {
                $query['query']['bool']['must'][] = [
                    'match_phrase'=>[
                        'name'=>[
                            'query'=>$name,
                        ]
                    ]
                ];
            }
        }
        //媒资包筛选
    }
}