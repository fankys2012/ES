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
use frame\Base;
use frame\helpers\FtpClient;
use frame\web\Controller;

class TestController extends Controller
{
    protected $mediaAssetsLogic = null;

    public function __construct($id, $module, array $config = [])
    {
        parent::__construct($id, $module, $config);

        $this->mediaAssetsLogic = new MediaAssetsLogic();
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
        $str = urlencode("TVseries#1000010|vstartek_tv#1000011|vstartek_av#1000011|vstartek_tb#1000011|vstartek_tc#1000011
        |vstartek_td#1000011|vstartek_te#1000011|vstartek_tf#1000011|vstartek_tg#1000011|vstartek_th#1000011
        |vstartek_ti#1000011|vstartek_tv#1000012|vstartek_tv#1000013|vstartek_tv#1000014|vstartek_tv#1000015|vstartek_tv#1000016
        |vstartek_tv#1000017|vstartek_tv#1000018|vstartek_tv#1000019|vstartek_tv#1000020");

        var_dump(memory_get_usage());
        echo "<br>";
        $c = urlencode("TVseries#1000010|vstartek_tv#1000011|vstartek_av#1000011|vstartek_tb#1000011|vstartek_tc#1000011
        |vstartek_td#1000011|vstartek_te#1000011|vstartek_tf#1000011|vstartek_tg#1000011|vstartek_th#1000011
        |vstartek_ti#1000011|vstartek_tv#1000012|vstartek_tv#1000013|vstartek_tv#1000014|vstartek_tv#1000015|vstartek_tv#1000016
        |vstartek_tv#1000017|vstartek_tv#1000018|vstartek_tv#1000019|vstartek_tv#1000020");;
        var_dump(memory_get_usage());
        echo "<br>";
        $c = urlencode("TVseries#1000010|vstartek_tv#1000011|vstartek_av#1000011|vstartek_tb#1000011|vstartek_tc#1000011
        |vstartek_td#1000011|vstartek_te#1000011|vstartek_tf#1000011|vstartek_tg#1000011|vstartek_th#1000011
        |vstartek_ti#1000011|vstartek_tv#1000012|vstartek_tv#1000013|vstartek_tv#1000014|vstartek_tv#1000015|vstartek_tv#1000016
        |vstartek_tv#1000017|vstartek_tv#100vstartek_tv#1000020");;
        var_dump(memory_get_usage());
        echo "<br>";
    }
}