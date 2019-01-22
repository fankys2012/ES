<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2019/1/3
 * Time: 13:52
 */

namespace api\timer;



use api\logic\KeywordsLogic;
use api\util\CacheRedis;

class KeywordsController extends Timer
{

    public function consumeQueueAction()
    {
        if(false === $this->check_linux_course()) {
            return false;
        }

        $keywordsLogic = new KeywordsLogic();

        $redisClient = CacheRedis::getInstance();
        while (true)
        {
            $list = $redisClient->zRange("qe:update_keywords_list",0,100);
            if(empty($list) || !is_array($list)) {
                break;
            }
            foreach ($list as $item) {
                $result = $keywordsLogic->updateMediaCites($item);
                $res = $redisClient->zRem('qe:update_keywords_list',$item);
            }
            break;
        }

    }
}



