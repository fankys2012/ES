<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2018/12/20
 * Time: 14:20
 */

namespace api\controllers;


use frame\Base;
use frame\web\Controller;

class ConfigController extends Controller
{

    public function setRuleAction()
    {
        $rules = Base::$app->request->getParam('rules');
        if(empty($rules)) {
            return $this->reponse(['ret'=>1,'reason'=>'params rules can not empty']);
        }

        $file = APP_DIR.'/config/rule.php';
        $arr_rules = json_decode(base64_decode($rules),true);
        $res = file_put_contents($file,"<?php \n return '".serialize($arr_rules).'\';');
        if(!$res) {
            return $this->reponse(['ret'=>1,'reason'=>'write file:'.$file.' failed']);
        }
        return $this->reponse(['ret'=>0,'reason'=>'write file:'.$file.' success']);
    }

    public function setWeightAction()
    {
        $data = Base::$app->request->getParam('data');
        if(empty($data)) {
            return $this->reponse(['ret'=>1,'reason'=>'params data can not empty']);
        }

        $file = APP_DIR.'/config/weight.php';
        $arr_rules = json_decode($data,true);
        $res = file_put_contents($file,"<?php \n return ".var_export($arr_rules,true).';');
        if(!$res) {
            return $this->reponse(['ret'=>1,'reason'=>'write file:'.$file.' failed']);
        }
        return $this->reponse(['ret'=>0,'reason'=>'write file:'.$file.' success']);
    }
}