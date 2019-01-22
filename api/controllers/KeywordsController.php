<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2018/12/12
 * Time: 16:13
 */

namespace api\controllers;


use api\logic\ESLogic;
use api\logic\KeywordsLogic;
use api\model\KeywordsModel;
use frame\Base;
use frame\helpers\BaseVarDumper;
use frame\Log;
use frame\web\Controller;
use api\logic\MediaAssetsSearchLogic;

class KeywordsController extends Controller
{
    //@var \api\model\KeywordsModel
    private $_keywordsModel = null;

    //@var \Elasticsearch\Client
    private $_esclient = null;

    private $_keywordsLogic = null;

    public function __construct($id, $module, array $config = [])
    {
        parent::__construct($id, $module, $config);

        $this->_esclient = ESLogic::getInstance()->connect();
        //var api\model\KeywordsModel
        $this->_keywordsModel = new KeywordsModel($this->_esclient);

        $this->_keywordsLogic = new KeywordsLogic();
    }


    public function getListAction()
    {
        $category = Base::$app->request->getParam('category','vod');
        $name = Base::$app->request->getParam('name');
        $from = Base::$app->request->getParam('from',0);
        $size = Base::$app->request->getParam('size',12);

        $query = [
            'query'=>[
                'bool'=>[
                    "must"=>[
                        ['term'=>[
                            'category'=>$category,
                        ]]
                    ]
                ]
            ],
//            'sort'=>[
//                'modify_time'=>[
//                    'order'=>'desc'
//                ]
//            ]
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
//                            'minimum_should_match'=>'100%'
                        ]
                    ]

                ];
            }
        }

        $result = $this->_keywordsLogic->getList($query,$from,$size);
        $this->reponse($result);
    }

    public function editAction()
    {
        $name = Base::$app->request->getParam('name');
        $id = Base::$app->request->getParam('id');
        $weight = Base::$app->request->getParam('weight');
        $t_click = Base::$app->request->getParam('t_click');//总点击数
        $state = Base::$app->request->getParam('state');
        if(empty($id)) {
            $this->reponse(['ret'=>1,'reason'=>'id not empty']);
        }
        //get keywords
        $info = $this->_keywordsLogic->getById($id);
        if($info['ret'] == 1)
        {
            return $this->reponse(['ret'=>1,'reason'=>'信息不存在']);
        }

        $params = [];
        $recreate = false;
        if($name !=null) {
            $new_id = md5($name.$info['data']['category']);
            if($id != $new_id)
            {
                $exist = $this->_keywordsLogic->getById($new_id);
                if($exist['ret'] == 0) {
                    return $this->reponse(['ret'=>1,'reason'=>'关键词已存在']);
                }
            }
            else
            {
                $recreate = true;
            }
            $params['name'] = $name;
        }
        if($weight !=null) {
            $params['weight'] = (int)$weight;
        }
        if($t_click != null) {
            $params['t_click'] = (int)$t_click;
        }
        if($state != null)
        {
            $params['state'] = (int)$state;
        }
        if($recreate === false)
        {
            $result = $this->_keywordsLogic->updateKeywords($id,$params);
            if($result['ret'] == 0) {
                $this->_keywordsLogic->refresh();
            }
            return $this->reponse($result);
        }
        else
        {
            $params = array_merge($info['data'],$params);
            $result = $this->_keywordsLogic->addKeywords($params,$new_id);
            if($result['ret'] == 0) {
                $this->_keywordsLogic->delKeywordById($id);
            }
            return $this->reponse($result);
        }

    }


    public function delAction()
    {
        $ids = Base::$app->request->getParam('ids');
        if($ids == null) {
            return $this->reponse(['ret'=>1,'reason'=>'id not empty']);
        }
        $arr_id = explode(',',$ids);
        $state = 1;
        $reason = 'success';

        foreach ($arr_id as $id)
        {
            $ret = $this->_keywordsLogic->delKeywordById($id);
            if($ret['ret'] ==0) {
                $state = 0;
            }
            else {
                $reason = $ret['reason'];
            }
        }
        $this->_keywordsLogic->refresh();
        $data = [
            'ret'=>$state,
            'reason'=>$reason
        ];

        return $this->reponse($data);
    }

    public function delAllAction()
    {
        $this->_keywordsModel->deleteAll();
        return $this->reponse(['ret'=>0,'reason'=>'success']);
    }

    /**
     * N39_a_65接口搜索
     */
    public function searchAction()
    {
        $category = Base::$app->request->getParam('category');
        $name = Base::$app->request->getParam('name');
        $from = Base::$app->request->getParam('from',0);
        $size = Base::$app->request->getParam('size',12);
        if(empty($name)){
            return $this->reponse(['ret'=>1,'reason'=>'params name can not empty']);
        }
        $filterParams = [
            'state'     => 1,
            'category'  =>$category,
            'cites_counter'=>0,
        ];

        $queryBool = [];
        $score_func = MediaAssetsSearchLogic::funcScore($category,'keywords');
        if($name) {
            $queryBool = MediaAssetsSearchLogic::keywordsBoolMatch($name);
        }
        $filterBool = MediaAssetsSearchLogic::boolFilter($filterParams);
        if($filterBool) {
            $queryBool = array_merge($queryBool,$filterBool);
        }

        $query = [
            '_source'=>[
                'includes'=>['name','original_id','category']
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
            ],
//            'highlight'=>MediaAssetsSearchLogic::keywordsHightlight(),

        ];
        Log::info("关键词搜索query:".BaseVarDumper::export($query));

        $result = $this->_keywordsLogic->getList($query,$from,$size);
        $this->reponse($result);
    }



}