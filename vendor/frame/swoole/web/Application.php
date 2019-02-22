<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2019/2/14
 * Time: 15:44
 */

namespace frame\swoole\web;

/**
 * Class Application
 * @package frame\web
 * @property \frame\swoole\web\Request $request
 */
class Application extends \frame\web\Application
{


    public function coreComponents()
    {
        $compontens = parent::coreComponents();
        $compontens['request'] = ['class' => 'frame\swoole\web\Request'];
        $compontens['response'] = ['class' => 'frame\swoole\web\Response'];
        return $compontens;
    }
}