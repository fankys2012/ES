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
        if($name) {
            $queryBool = MediaAssetsSearchLogic::boolMatch($name);
        }
        $query = [
            '_source'=>[
                'includes'=>['name','original_id','asset_type','category']
            ],
            'query'=>[
                'function_score'=>[
                    'query'=>[
                        'bool'=>$queryBool,
                    ],
                    'functions'=>$score_func,
                    'boost_mode'=>'sum',//multiply,replace,sum,avg,max,min
                    'score_mode'=>'sum',//multiply,sum,avg,first,max,min

                ],
            ]
        ];

//        return $this->reponse($query);

        //媒资包筛选

        $result = $this->mediaAssetsLogic->getList($query,$from,$size);

        $this->reponse($result);
    }
}