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
use frame\helpers\BaseVarDumper;
use frame\Log;
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
//        var_dump($post_data);exit;
        $resultList = [];
        foreach ($post_data as $item)
        {
            $res = null;
            if($item['operate'] == 'update')
            {
                //兼容上线时间格式问题
                if(isset($item['relase_date']) && trim($item['relase_date']) == '0000-00-00') {
                    $item['relase_date'] = '1970-01-01';
                }
                unset($item['operate']);
                $res = $this->mediaAssetsLogic->updateMediaAssetDoc($item);
            }
            elseif($item['operate'] == 'package')
            {
                $_id = md5($item['original_id'].$item['source']);
                $params = [
                    'package'=>isset($item['package'])?$item['package']:[],
                    'state'=> (!isset($item['package']) || count($item['package'])< 1 ) ? 0: 1,
                ];
                $res = $this->mediaAssetsLogic->mediaAsstesDocModel->editDoc($params,$_id);
                if($res['ret'] ==0)
                {
                    $doc = $this->mediaAssetsLogic->mediaAsstesDocModel->getDocById($_id);
                    $this->mediaAssetsLogic->keywordsUpdateQueue($doc['data']['kw_cites']);
                }
            }
            elseif ($item['operate'] == 'delete')
            {
                $_id = md5($item['original_id'].$item['source']);
                if($item['category'] =='vod') {
                    $doc = $this->mediaAssetsLogic->mediaAsstesDocModel->getDocById($_id);
                    $this->mediaAssetsLogic->keywordsUpdateQueue($doc['data']['kw_cites']);
                }
                else {
                    //删除关键词
                    $this->mediaAssetsLogic->keywordsLogic->delKeywordById($_id);
                }
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

    /**
     * 内容搜索
     */
    public function searchAction()
    {
        $category = Base::$app->request->getParam('category');
        $name = Base::$app->request->getParam('name');
        $from = Base::$app->request->getParam('from',0);
        $size = Base::$app->request->getParam('size',12);

        $filterParams = [
            'state'     =>Base::$app->request->getParam('state'),
            'category'  =>$category,
            'asset_type'=>Base::$app->request->getParam('asset_type'),
            'cp_id'     =>Base::$app->request->getParam('cp_id'),
            'package'   =>Base::$app->request->getParam('package'),
            'epg_tag'   =>Base::$app->request->getParam('epg_tag'),
        ];


        $queryBool = [];
        $score_func = MediaAssetsSearchLogic::funcScore($category,'search');
        if($name) {
            $queryBool = MediaAssetsSearchLogic::boolMatch($name);
        }
        $filterBool = MediaAssetsSearchLogic::boolFilter($filterParams);

        if($filterBool) {
            $queryBool = array_merge($queryBool,$filterBool);
        }

        $query = [
            '_source'=>[
                'includes'=>['name','original_id','asset_type','category','t_click']
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
        $aggsField = ['category','asset_type'];
        $aggs = MediaAssetsSearchLogic::assetsAggs($aggsField);
        $query['aggs'] = $aggs;
        Log::info('媒资搜索query：'.BaseVarDumper::export($query));

        $result = $this->mediaAssetsLogic->getList($query,$from,$size);

        $this->reponse($result);
    }

    /**
     * cms 后台媒资搜索列表
     */
    public function backendSearchAction()
    {
        $name = Base::$app->request->getParam('name');
        $category = Base::$app->request->getParam('category','vod');
        $order = Base::$app->request->getParam('order','t_click');
        $from = Base::$app->request->getParam('from',0);
        $size = Base::$app->request->getParam('size',12);
        $query = [
            'sort'=>[
                [$order=>"desc"],
                "_score"
            ],
            'query'=>[
                'bool'=>[
                    'filter'=>[
                        'bool'=>[
                            'must'=>[
                                [
                                    'term'=>[
                                        'category'=>$category
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        if($name) {
            if(preg_match ("/^[A-Za-z]+$/u", $name)) {
                $query['query']['bool']['must'][] = [
                    'multi_match'=>[
                        'query'=>$name,
                        'type'=>'phrase_prefix',
                        'fields'=>['name.full_pinyin']
                    ]
                ];
            }
            else {
                $query['query']['bool']['must'][] = [
                    'multi_match'=>[
                        'query'=>$name,
                        'type'=>'phrase_prefix',
                        'fields'=>['name']
                    ]
                ];
            }
        }
        $result = $this->mediaAssetsLogic->mediaAsstesDocModel->getList($query,$from,$size);
        $this->reponse($result);
    }

    /**
     * cms后台更新媒资权重和点击数
     */
    public function backendEditAction()
    {
        $id = Base::$app->request->getParam('id');
        $field = [
            'weight'=>Base::$app->request->getParam('weight'),
            't_click'=>Base::$app->request->getParam('t_click'),
        ];

        $editField = $this->mediaAssetsLogic->mediaAsstesDocModel->getEditFieldsData($field);
        if(empty($id) || empty($editField)) {
            return $this->reponse(['ret'=>1,'reason'=>'参数错误']);
        }
        $result = $this->mediaAssetsLogic->mediaAsstesDocModel->editDoc($editField,$id);
        $this->mediaAssetsLogic->mediaAsstesDocModel->refresh();
        $this->reponse($result);

    }
}