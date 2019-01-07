<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2019/1/2
 * Time: 10:54
 */

namespace api\controllers;


use api\logic\ESLogic;
use api\logic\KeywordsLogic;
use api\logic\MediaAssetsLogic;
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

}