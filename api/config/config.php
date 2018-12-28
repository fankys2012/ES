<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2018/11/30
 * Time: 17:11
 */
\frame\Base::setAlias('@api', dirname(dirname(__DIR__)) . '/api');
return [
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'api\controllers',

    'params'=>[
        //elasticsearch address
        'es' => [
            '192.168.56.120:9200'
        ],
        'es_log_level'=>100,// DEBUG：100 ；INFO：200 ；NOTICE：250；WARNING：300；ERROR：400；ALERT：550
    ],

];