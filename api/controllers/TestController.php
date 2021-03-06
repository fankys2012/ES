<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2019/1/2
 * Time: 10:54
 */

namespace api\controllers;


use api\logic\ClickSyncLogic;
use api\logic\ESLogic;
use api\logic\KeywordsLogic;
use api\logic\MediaAssetsLogic;
use api\util\CacheRedis;
use frame\Base;
use frame\helpers\BaseVarDumper;
use frame\helpers\FtpClient;
use frame\Log;
use frame\web\Controller;

class TestController extends Controller
{
    protected $mediaAssetsLogic = null;

    public function __construct($id, $module, array $config = [])
    {
        parent::__construct($id, $module, $config);

//        $this->mediaAssetsLogic = new MediaAssetsLogic();
    }

    /**
     * 媒资点击数更新
     */
    public function mediaAssetsAction()
    {
        $params = $_REQUEST;
        $result = $this->mediaAssetsLogic->updateMediaAssetsClicks($params);
        return $this->reponse($result);
    }

    public function keywordsAction()
    {
        $keywordsLogic = new KeywordsLogic();
        $result = $keywordsLogic->updateClick($_REQUEST);
        return $this->reponse($result);
    }


    public function ftpAction()
    {
        $list['75580aa6a3345fb7bcb8d7b46a6391f9'] = [
            'original_id'=>'06070fc25f8ad259b95408146481dd6b',
            'oned_click'=>11,
            'sd_click'=>22,
            'fth_click'=>33,
            'm_click'=>44,
        ];
        $list['81617cc3aa8f3fce625758181e6c1201'] = [
            'original_id'=>'0039473cfbb6c6d3064d8e6c8af46f3e',
            'oned_click'=>111,
            'sd_click'=>222,
            'fth_click'=>333,
            'm_click'=>444,
        ];
        $ClickSyncLogic = new ClickSyncLogic();
//        $result = $ClickSyncLogic->syncClicks($list,true);
        Log::info($list);
        $arr[4] = 1;
        $avg_sd = 1;
        if(isset($arr[4]) && $arr[4]) {
            $avg_sd = ceil($arr[4]/7);
        }
        echo $avg_sd;
//        Log::flush();
    }

    public function testAction()
    {

        $post_data = Base::$app->request->getParam('post_data');
        $newstr = '100600018749';
        echo "1:".decbin(1)."<br>";
        echo "2:".decbin(2)."<br>";
        echo "3:".decbin(3)."<br>";
        echo "4:".decbin(4)."<br>";
        $a = 1;
        $b = 2;
        echo $a & $b;
//        $newstr |=  "aaab";
//        echo $newstr;
//        return $newstr;
    }

    public function redisAction()
    {
        $redisClient = CacheRedis::getInstance();
        $luaScript = file_get_contents(APP_DIR.'/config/test.lua');
        $res = $redisClient->eval($luaScript,array('keyword','key2','72665554668a4d8e1a35c5b511fb507b','second'),2);
        var_dump($res);
    }
}