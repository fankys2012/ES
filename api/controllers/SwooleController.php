<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2019/2/22
 * Time: 17:55
 */

namespace api\controllers;


use api\util\DoubleLink;
use frame\Log;
use frame\web\Controller;


class SwooleController extends Controller
{
    public function goAction()
    {
        $doubleLink = new DoubleLink();
        $doubleLink->add('1');
        $doubleLink->add('2');
        $doubleLink->append('3');
        $doubleLink->append('4');
        echo "<pre>";
        print_r($doubleLink);
    }

    public function coroutineAction()
    {
        Log::error('This is a test');
        \Swoole\Runtime::enableCoroutine();
        $start = microtime(true);

        $chan = new \Swoole\Coroutine\Channel(1);

        go(function () use ($chan)
        {
            sleep(1);
            $chan->push("func1");
            echo "b\n";
        });

        go(function () use($chan)
        {
            sleep(2);
            $chan->push("func2");
            echo "c\n";
        });

        go(function () use ($chan){
            while (true) {
                echo "func3 get pop:";
                echo $chan->pop()."\n";
            }

        });

        echo (microtime(true)-$start)."\n";

        return "coroutine Action()";
    }
}