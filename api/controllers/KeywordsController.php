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
use frame\web\Controller;

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


    public function createIndexAction()
    {
        $result = $this->_keywordsModel->CreateIndex();
        echo json_encode($result);
    }

    public function createMappingAction()
    {
        $result = $this->_keywordsModel->CreateMapping();
        echo json_encode($result);
    }

    public function getMappingAction()
    {
        $result = $this->_keywordsModel->getMapping();
        echo "<pre>";
        print_r($result);
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
                    'match'=>[
                        'name'=>[
                            'query'=>$name,
                            'minimum_should_match'=>'100%'
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

    public function addAction()
    {
        $date = date('Y-m-d H:i:s',time());
        //类型
        $category = Base::$app->request->getParam('category','vod');
        $original_id = Base::$app->request->getParam('original_id');
        $soruce = Base::$app->request->getParam('soruce','cms');

        $params = array(
            'name'         => Base::$app->request->getParam('name'),
            'category'     => [$category],
            'weight'       => (int)Base::$app->request->getParam('weight',0),//权重
            't_click'      => (int)Base::$app->request->getParam('t_click',0),//总点击数
            'state'        => (int)Base::$app->request->getParam('state',1),//状态 1：启用 0：禁用
            'oned_click'   => (int)Base::$app->request->getParam('oned_click',0),//1日点击量
            'sd_click'     => (int)Base::$app->request->getParam('sd_click',0),//7日点击量
            'sd_avg_click' => (int)Base::$app->request->getParam('sd_avg_click',0),//7日日均点击量
            'fth_click'    => (int)Base::$app->request->getParam('fth_click',0),//15日点击量
            'fth_agv_click'=> (int)Base::$app->request->getParam('fth_agv_click',0),//15日日均点击量
            'm_click'      => (int)Base::$app->request->getParam('m_click',0),//30日点击量
            'm_agv_click'  => (int)Base::$app->request->getParam('m_agv_click',0),//30日日均点击量
            'create_time'  => $date, //创建时间
            'modify_time'  => $date,//修改时间
            'original_id'  => $original_id,
            'source'       => $soruce,
            'cites_counter'=> 0,
        );
        if($category == 'star') {
            if(empty($original_id)) {
                return $this->reponse(['ret'=>1,'reason'=>'original_id can not empty']);
            }
            $id = md5($original_id.$soruce);
        }
        else {
            $id = md5($params['name']);
        }

        $exist = $this->_keywordsLogic->getById($id);
        if($exist['ret'] == 0) {
            $old_category = $exist['data']['category'];
            $edit_params = [
                'name'=>Base::$app->request->getParam('name'),
                'category' => array_unique(array_merge($old_category,array($category)))
            ];
            $result = $this->_keywordsLogic->updateKeywords($id,$edit_params);
            if($result['ret'] == 0) {
                // $this->_keywordsLogic->refresh();
            }
            return $this->reponse($result);
        }
        else {
            $result = $this->_keywordsLogic->addKeywords($params,$id);
            if($result['ret'] == 0) {
                $this->_keywordsLogic->refresh();
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

    public function testAction()
    {
        $str = "aaaa";
        $patterns = [
            "/第(.)+季/",
            "/高清$/",
            "/^(\d)+/"
        ];
        $replace = [
            "",
        ];
        $str = "a11222a天龙八部";
        $new_str = preg_replace($patterns,$replace,$str);
        echo $new_str;


    }
}