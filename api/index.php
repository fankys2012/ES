<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2018/11/30
 * Time: 11:34
 */

header("Content-Type:text/html;charset=utf-8");
require_once (__DIR__ . '/../vendor/autoload.php');
require_once (__DIR__ . '/../vendor/frame/Init.php');
define('APP_DIR',__DIR__);

$arr_config = require (__DIR__ . '/config/config.php');

$application = new frame\web\Application($arr_config);
$application->run();
