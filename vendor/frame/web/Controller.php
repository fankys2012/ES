<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2018/11/30
 * Time: 11:45
 */

namespace frame\web;


class Controller extends \frame\base\Controller
{

    protected function reponse($data,$output_type='json')
    {
        if($output_type == 'json') {
            header("Content-Type:application/json;charset=utf-8");
            if(is_array($data)) {
                echo json_encode($data);
            }
            else {
                echo $data;
            }
        }
        exit;
    }

}