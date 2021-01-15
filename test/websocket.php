<?php
/**
 * Created by PhpStorm.
 * User: liumingyu
 * Date: 2021/1/12
 * Time: 3:44 PM
 */

require_once __DIR__ ."/../vendor/autoload.php";

class Websockett extends \tebie6\swoole\console\SwooleService
{
    public $project = [
        'server'=>[
            'tebie6\swoole\project\ChatWebsocketServer',
        ],
    ];
}

$ws = new Websockett();
$ws->start();