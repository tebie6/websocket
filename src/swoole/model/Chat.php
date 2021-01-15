<?php
/**
 * Created by PhpStorm.
 * User: liumingyu
 * Date: 2021/1/12
 * Time: 5:53 PM
 */

namespace tebie6\swoole\model;

class Chat
{

    public $logPath = __DIR__ . '/../logs/';

    /**
     * 验证用户合法性
     *
     * demo|##|wap|##|uid
     * 格式说明： 业务标识|##|应用ID|##|uid
     *
     * {
    "username":"demo|##|chat_app|##|uid", //分隔符：|##|
    "password":"123456", //token
    "role":"2",//角色 (1系统消息 2注册用户 3客服 5游客 )
    }
     */
    public function actionUserAuth()
    {
        $username = Yii::$app->request->post('username');
        $password = Yii::$app->request->post('password');
        $role = Yii::$app->request->post('role');

        //debug
        OUtils::makeFile($username.'-'.$password.'-'.$role, $this->logPath . 'ws' . DIRECTORY_SEPARATOR . 'auth_' . date('Y-m-d', time()) . '.log', 1);

        $user = explode('|##|',$username);
        file_put_contents('/tmp/log.txt',$username.'-'.$password.PHP_EOL,FILE_APPEND);
        $bsid = $user[0];
        $source = $user[1];
        $uid = $user[2];
        return ['result' => 'pass', 'uid' => intval($uid)];

        if(!ImService::userAuth($bsid,$source,$uid,$password,$role)){
            OUtils::makeFile($username.'-'.$password.'-'.$role.'-denied', $this->logPath . 'ws' . DIRECTORY_SEPARATOR . 'auth_' . date('Y-m-d', time()) . '.log', 1);
            return ['result' => 'denied'];
        }else{
            return ['result' => 'pass', 'uid' => intval($uid)];
        }

    }

}