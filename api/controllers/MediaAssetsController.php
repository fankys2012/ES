<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2018/12/21
 * Time: 14:11
 */

namespace api\controllers;


use api\logic\MediaAssetsLogic;
use frame\web\Controller;

class MediaAssetsController extends Controller
{
    protected $mediaAssetsLogic = null;

    public function __construct($id, $module, array $config = [])
    {
        parent::__construct($id, $module, $config);

        $this->mediaAssetsLogic = new MediaAssetsLogic();
    }

    public function createAction()
    {
//        $index = $this->mediaAssetsLogic->mediaAsstesDocModel->createIndex();
//        if($index['ret'] ==1) {
//            return $this->reponse($index);
//        }
        $mapping = $this->mediaAssetsLogic->mediaAsstesDocModel->createMapping();
        return $this->reponse($mapping);
    }

    public function testAction()
    {
        $docList = [
            'name'=>'天龙八部',
            'alias_name'=>'',
            'summary'=>'故事以北宋末年动荡的社会环境为背景，展开波澜壮阔的历史画卷，塑造了乔峰、段誉、 虚竹、慕容复等形象鲜明的人物，成为武侠史上的经典之作。故事精彩纷呈，人物命运悲壮多变，是可读性很强的作品，具有震撼人心的力量',
            'director'=>[
                [
                    'name'=>'周晓文',
                    'id'=>'10001'
                ],
                [
                    'name'=>'胡军',
                    'id'=>'10002'
                ],
            ],
            'category'=>'vod'
        ];
        $result = $this->mediaAssetsLogic->mediaAsstesDocModel->addDoc($docList,'10001');
        return $this->reponse($result);

    }

    public function reciveAction()
    {

    }
}