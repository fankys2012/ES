<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2019/3/6
 * Time: 13:37
 */

namespace api\controllers;


use api\logic\StatisticLogic;
use frame\Base;
use frame\Log;
use frame\web\Controller;

class StatisticController extends Controller
{

    public function statisticAction()
    {
        $doc_id = Base::$app->request->getParam('id');
        $doc_type = Base::$app->request->getParam('type');
        if(empty($doc_id) || empty($doc_type)) {
            return $this->reponse(['ret'=>1,'reason'=>'params empty']);
        }
        Log::info("统计：doc_id:{$doc_id} , doc_type:{$doc_type}");
        $statisticLogic = new StatisticLogic();
        //文档内容统计
        if($doc_type == 'content') {
            $result = $statisticLogic->ContentStatistic($doc_id);
        }
        elseif($doc_type = 'keywork') {
            $result = $statisticLogic->KeywordsStatistic($doc_id);
        }
        else {
            $result = ['ret'=>1,'reason'=>'type error'];
        }
        return $this->reponse($result);
    }

}