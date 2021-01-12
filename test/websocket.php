<?php
/**
 * Created by PhpStorm.
 * User: liumingyu
 * Date: 2021/1/12
 * Time: 3:44 PM
 */

require_once "../vendor/autoload.php";

class Websockett extends \tebie6\swoole\console\SwooleService
{

}

$ws = new Websockett();
$ws->start();