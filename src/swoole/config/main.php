<?php
/**
 * Created by PhpStorm.
 * User: liumingyu
 * Date: 2021/1/12
 * Time: 3:50 PM
 */

return [
    'websocket' => [
        'class' => 'common\components\swoole\console\SwooleController',
        'rootDir' => str_replace('console/config', '', __DIR__ ),//yii2项目根路径
        'app' => 'backend',
        'host' => '0.0.0.0',                                    //默认监听所有机器  可以填写127.0.0.1只监听本机 详见swoole文档
        'port' => 9998,
        'web' => 'web',                                         //默认为web rootDir app web目的是拼接yii2的根目录
        'debug' => true,                                        //默认开启debug，上线应置为false
        'env' => 'dev',                                         //默认为dev，上线应置为prod
        'swooleConfig' => [                                     //swoole 相关配置 详见swoole文档
            'reactor_num' => 2,
            'worker_num' => 4,
            'task_worker_num' => 4,
            'daemonize' => false,
            'log_file' => __DIR__ . '/../../console/runtime/logs/swoole.log',
            'log_level' => 0,
            'pid_file' => __DIR__ . '/../../console/runtime/server.pid',
        ],
        'project'=>[                                           //项目server路径 根据实际情况填写
            'server'=>[
                'common\components\swoole\project\ChatWebsocketServer',
            ],
        ]
    ]
];