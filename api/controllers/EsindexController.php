<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2018/11/30
 * Time: 11:44
 */

namespace api\controllers;


use api\logic\ESLogic;
use api\model\DataSourceModel;
use Elasticsearch\ClientBuilder;
use frame\Base;
use frame\web\Controller;


class EsindexController extends Controller
{
    public function CreateAction()
    {
        $ESClient = ESLogic::getInstance()->connect();
        print_r($ESClient);
        $a = new DataSourceModel();
    }
}