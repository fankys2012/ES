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
            '192.168.56.120:9200',
//            '192.168.95.99:9200'
        ],
        'es_log_level'=>100,// DEBUG：100 ；INFO：200 ；NOTICE：250；WARNING：300；ERROR：400；ALERT：550
        'redis'=>[
            'host'=>'127.0.0.1',
            'port'=>'6379',
            'password'=>'',
            'timeout'=>3,
        ],
        'clickSyncFtp'=>[
            'address'=>'192.168.95.13',
            'user'=>'hftv',
            'password'=>'internetTV1688',
            'port'=>'21',
            'time_out'=>15,
            'root_dir'=>'/data/starcor/www/preview/internettv/prev/ksimg'
        ],
        'clickSyncCp'=>'HNDX',
    ],

];