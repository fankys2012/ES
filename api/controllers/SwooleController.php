<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2019/2/22
 * Time: 17:55
 */

namespace api\controllers;


use frame\Log;
use frame\log\Logger;
use frame\web\Controller;

class SwooleController extends Controller
{
    public function goAction()
    {
        return "go Action()";
    }

    public function coroutineAction()
    {
        Log::error('This is a test');
        return "coroutine Action()";
    }
}