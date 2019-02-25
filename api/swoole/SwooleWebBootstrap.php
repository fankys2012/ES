<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2019/2/18
 * Time: 10:43
 */

namespace api\swoole;


use frame\base\Event;
use frame\swoole\web\Application;

class SwooleWebBootstrap extends SwooleBaseBootstrap
{

    public function handleRequest($resquest, $response)
    {
        $application = new Application($this->appConfig);
        //在 file 日志模式下重新创建日志文件
        $application->getLog()->createLogFile();
        $application->getRequest()->setSwooleReques($resquest);
        $application->getResponse()->setSwooleResponse($response);
        $application->on(Application::EVENT_AFTER_ACTION,[$this,'onRequestEnd']);
        try{
            $reponse = $application->handleRequest($application->getRequest());
            $reponse->send();
            $application->trigger(Application::EVENT_AFTER_ACTION);
            return $reponse->exitStatus;
        }
        catch (\Exception $e) {

        }

    }

    public function onRequestEnd(Event $event)
    {
        /**
         * @var Application $application
         */
        $application = $event->sender;
        $application->getLog()->getLogger()->flush(true);
    }
}