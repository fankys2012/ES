<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2018/12/18
 * Time: 11:00
 */

namespace api\controllers;


use api\logic\KeywordsLogic;
use api\logic\StarLogic;
use frame\Base;
use frame\web\Controller;

class MetadataController extends Controller
{
    protected $starLogic = null;
    protected $keywordsLogic = null;

    public function __construct($id, $module, array $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->starLogic = new StarLogic();
        $this->keywordsLogic = new KeywordsLogic();
    }

    /**
     * 明星信息
     *
     */
    public function starAction()
    {
        $post_data = Base::$app->request->getParam('post_data');
        if(empty($post_data) || count($post_data) < 1) {
            return $this->reponse(['ret'=>1,'reason'=>'post_data is empty']);
        }
        $starKeywords = [];

        $reponseMsg = [
            'success'=>[],
            'failed' =>[],
        ];

        foreach ($post_data as $item) {
            $info = $this->starLogic->getAddFeilds($item);
            if($info['ret'] == 1) {
                $reponseMsg['failed'][]= ['id'=>$info['data']['msg_id'],'msg'=>$info['reason']];
                continue;
            }
            $exists = $this->keywordsLogic->getById($info['data']['_id']);
            if($exists['ret'] == 1) {
                $starKeywords[] = $info['data'];
            }
            else {
                $edit = $this->starLogic->getEditFields($item);
                $result = $this->keywordsLogic->updateKeywords($info['data']['_id'],$edit);
                if($result['ret'] ==1) {
                    $reponseMsg['failed'][]= ['id'=>$item['msg_id'],'msg'=>$result['reason']];
                }
                else {
                    $reponseMsg['success'][] = ['id'=>$item['msg_id'],'msg'=>'success'];
                }
            }
        }
        if(count($starKeywords) >0) {
            $result = $this->keywordsLogic->batchUpdate($starKeywords);
            $reponseMsg['success'] = array_merge($reponseMsg['success'],$result['data']['success']);
            $reponseMsg['failed'] = array_merge($reponseMsg['failed'],$result['data']['failed']);
        }

        return $this->reponse(['ret'=>0,'reason'=>'success','data'=>$reponseMsg]);
    }

    public function starDeleteAction()
    {
        $post_data = Base::$app->request->getParam('post_data');
        if(empty($post_data) || count($post_data) < 1) {
            return $this->reponse(['ret'=>1,'reason'=>'post_data is empty']);
        }
        $reponseMsg = [
            'success'=>[],
            'failed' =>[],
        ];

        foreach ($post_data as $item) {
            $id = md5($item['original_id'].$item['soruce']);
            $result = $this->keywordsLogic->delKeywordById($id);
            if($result['ret'] ==0) {
                $reponseMsg['success'][] = ['id'=>$item['msg_id'],'msg'=>'success'];
            }
            else {
                $reponseMsg['failed'][]= ['id'=>$item['msg_id'],'msg'=>$result['reason']];
            }
        }


        return $this->reponse(['ret'=>0,'reason'=>'success','data'=>$reponseMsg]);
    }
}