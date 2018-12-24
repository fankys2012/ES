<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2018/12/21
 * Time: 14:14
 */

namespace api\logic;


use api\model\MediaAssetsDoc;
use frame\Base;

class MediaAssetsLogic
{
    public $esclient=null;

    public $mediaAsstesDocModel = null;

    public function __construct()
    {
        $this->esclient = ESLogic::getInstance();

        $this->mediaAsstesDocModel = new MediaAssetsDoc($this->esclient->connect());
    }

    public function updateMediaAssetDoc($params)
    {
        if(!isset($params['original_id']) || empty($params['original_id'])) {
            return ['ret'=>1,'reason'=>'params original_id can not empty'];
        }

        if(!isset($params['source']) || empty($params['source'])) {
            $params['source'] = 'cms';
        }

        $_id = md5($params['original_id'].$params['source']);
        //若媒资包栏目未空 则表示未上线，未上线的数据状态置为不可用状态
        if(count($params['package']) <1) {
            $params['state'] = 0;
        }

        $exists = $this->mediaAsstesDocModel->getDocById($_id);
        if($exists['ret'] == 0 && $exists['data']['_id']) {
            $params['modify_time'] = Base::$curr_date_time;
            $result = $this->mediaAsstesDocModel->editDoc($params,$_id);
        }
        else {
            $params['modify_time'] = Base::$curr_date_time;
            $params['create_time'] = Base::$curr_date_time;
            $result = $this->mediaAsstesDocModel->addDoc($params,$_id);
        }
        return $result;

    }


}