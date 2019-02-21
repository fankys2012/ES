<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2019/2/18
 * Time: 10:43
 */

namespace api\swoole;


use frame\swoole\web\Application;

class SwooleWebBootstrap extends SwooleBaseBootstrap
{

    public function handleRequest($resquest, $response)
    {
        echo "SwooleWebBootstrap:handleRequest";
        $application = new Application($this->appConfig);
        $application->getRequest()->setSwooleReques($resquest);
        $application->getResponse()->setSwooleResponse($response);

//        try{
//            $reponse = $application->handleRequest($application->getRequest());
//            $reponse->send();
//            return $reponse->exitStatus;
//        }
//        catch (\Exception $e) {
//
//        }
        $response->end("<h1>Hello Swoole!</h1>".var_export($resquest->get,true));

    }
}