<?php
namespace tebie6\swoole\project;

/**
 * 具体项目websocket server 根据业务要求重写websocket的相关方法
 */
use tebie6\swoole\model\ConnectMysqli;
use tebie6\swoole\model\Redis;
use tebie6\swoole\server\WebsocketServer;

defined('CONNECT') or define('CONNECT', 1);         // 客户端请求连接到服务器
defined('CONNECT_ACK') or define('CONNECT_ACK', 2); // 服务器通知客户端:连接确认
defined('PUBLISH') or define('PUBLISH', 3);         // 发布消息
defined('PUB_ACK') or define('PUB_ACK', 4);         // 发布消息确认
defined('GROUP_PUBLISH') or define('GROUP_PUBLISH', 5); // 发布群组消息
defined('GROUP_PUB_ACK') or define('GROUP_PUB_ACK', 6); // 发布群组消息确认
defined('PING') or define('PING', 7);               // 心跳请求
defined('PING_RESP') or define('PING_RESP', 8);     // 心跳确认
defined('DISCONNECT') or define('DISCONNECT', 9);   // 客户端或服务端断开

/**
 * 聊天 websocket 服务
 * Class ChatWebsocketServer
 * @package tebie6\swoole\project
 */
class ChatWebsocketServer extends WebsocketServer
{

    public $conn;
    public $redis;
    public $timer = [];

    /**
     * 监听ws连接事件 继承必须实现
     * function onOpen(swoole_websocket_server $svr, swoole_http_request $req);
     * $req 是一个Http请求对象，包含了客户端发来的握手请求信息
     * onOpen事件函数中可以调用push向客户端发送数据或者调用close关闭连接
     * onOpen事件回调是可选的
     */
    public function onOpen($swoole, $request)
    {
        $this->initDb();
        echo "request->fd:{$request->fd}\n";
    }

    /**
     * 监听ws消息事件 继承必须实现
     * function onMessage(swoole_server $server, swoole_websocket_frame $frame)
     * $frame 是swoole_websocket_frame对象，包含了客户端发来的数据帧信息
     * 共有4个属性，分别是
     *   $frame->fd，客户端的socket id，使用$server->push推送数据时需要用到
     *   $frame->data，数据内容，可以是文本内容也可以是二进制数据，可以通过opcode的值来判断
     *   $frame->opcode，WebSocket的OpCode类型，可以参考WebSocket协议标准文档
     *   $frame->finish， 表示数据帧是否完整，一个WebSocket请求可能会分成多个数据帧进行发送
     */
    public function onMessage($swoole, $frame)
    {
        // 判断数据内容的格式是否为二进制内容
        if ($frame->opcode != WEBSOCKET_OPCODE_BINARY) {

            // 断开链接
            $swoole->close($frame->fd);
            return;
        }

        //读取 1个字节 请求类型
        $requestType = unpack("Ctype", $frame->data);

        // 建立连接
        if ($requestType['type'] == CONNECT) {

            // 获取内容
            $body = unpack("C*", $frame->data);
            // 用户名长度
            $usernameLen = array_slice($body, 1, 2)[1];
            // 用户名
            $username = array_slice($body, 3, $usernameLen);
            // 密码长度
            $passwordLen = array_slice($body, 3 + $usernameLen, 2)[1];
            // 密码
            $password = array_slice($body, 3 + $usernameLen + 2, $passwordLen);
            // 角色
            $role = array_slice($body, 3 + $usernameLen + 2 + $passwordLen, 1);

            echo "username:" . $this->byteToString($username) . PHP_EOL;
            echo "password:" . $this->byteToString($password) . PHP_EOL;
            echo "role:" . $this->byteToString($role) . PHP_EOL;
            $username = explode("|##|", $this->byteToString($username));

            // 调用验证
//            $args = [
//                'url' => 'http://ws.tebie6.com/service/user-auth',
//                'data' => [
//                    'username' => $this->byteToString($username),
//                    'password' => $this->byteToString($password),
//                    'role' => $this->byteToString($role),
//                ],
//            ];
//            $OCurl = new OCurl();
//            $result = $OCurl::post($args);
//            if (!$result) {
//                $swoole->close($frame->fd);
//            }
//
//            $responseData = json_decode($OCurl::getData(), true);
//            if ($responseData['result'] == 'denied') {
//                $swoole->close($frame->fd);
//            }
            $responseData['uid'] = $username[2];

            // 绑定链接
            $this->bind($responseData['uid'], $frame->fd);

            // 心跳检测 服务器轮询请求客户的方式很浪费服务器资源 建议由客户端主动请求 服务器设置 heartbeat_idle_time、heartbeat_check_interval 参数
            $this->redis->hset("fd_{$frame->fd}", "start_time", time());
            $this->timer[$frame->fd][] = swoole_timer_tick(2000, function ($timerId) use ($swoole, $frame) {

                $startTime = $this->redis->hget("fd_{$frame->fd}", "start_time");
                if (time() - $startTime > 10) {

                    // 关闭连接
                    $swoole->close($frame->fd);
                }

                // 心跳请求
                $swoole->push($frame->fd, pack("C1", PING), WEBSOCKET_OPCODE_BINARY);
            });


        } // 发布消息
        else if ($requestType['type'] == PUBLISH) {

            $binaryArr = unpack("C1type/C4ToUid/C32MsgId/C2PayloadLen/C*Body", $frame->data);

            // 目标用户ID
            $toUid = 0;
            $toUid += $binaryArr["ToUid1"] * pow(256, 3);
            $toUid += $binaryArr["ToUid2"] * pow(256, 2);
            $toUid += $binaryArr["ToUid3"] * pow(256, 1);
            $toUid += $binaryArr["ToUid4"];

            // msgID 用来做消息确认，服务器消息后，会将相应的MsgId返回个客户端
            $msgId = "";
            for ($i = 1; $i <= 32; $i++) {
                $msgId .= chr($binaryArr["MsgId" . $i]);
            }

            // 后续的payload的字节数
            $payloadLen = 0;
            $payloadLen += $binaryArr["PayloadLen1"] * pow(256, 1);
            $payloadLen += $binaryArr["PayloadLen2"];


            $payload = '';
            for ($i = 1; $i <= $payloadLen; $i++) {
                $payload .= chr($binaryArr["Body" . $i]);
            }

            // 确认接收
            $confirmData = [];
            $confirmData[] = pack("C1", PUB_ACK);
            foreach (str_split($msgId) as $_k => $_v) {
                $confirmData[] = $this->stringToByte(pack("C1", $_v));
            }
            $swoole->push($frame->fd, implode("", $confirmData), WEBSOCKET_OPCODE_BINARY); //推送到接收者

            // 数据加入队列
//            $args = [
//                'url' => 'http://ws.tebie6.com/service/message',
//                'content-type' => 'application/json',
//                'data' => $payload,
//            ];
//            $OCurl = new OCurl();
//            $result = $OCurl::post($args);
//            $responseData = json_decode($OCurl::getData(), true);

            // 推送消息
            $pushData = [];
            $pushData[] = pack("C1", PUBLISH);

            // 处理 $msgId
            foreach (str_split($msgId) as $_k => $_v) {
                $pushData[] = $this->stringToByte(pack("C1", $_v));
            }
            // PayloadLen
            $pushData[] = pack("C1", $binaryArr["PayloadLen1"]);
            $pushData[] = pack("C1", $binaryArr["PayloadLen2"]);

            // Payload
            foreach (str_split($payload) as $_k => $_v) {
                $pushData[] = pack("C1", $this->stringToByte($_v));
            }

            // 推送给目标用户
            $toFd = $this->getFd($toUid);
            if ($toFd) {
                $swoole->push($toFd, implode("", $pushData), WEBSOCKET_OPCODE_BINARY);
            }


        } // 心跳确认
        else if ($requestType['type'] == PING_RESP) {

            // 更新时间
            $this->redis->hset("fd_{$frame->fd}", "start_time", time());
        }

    }

    /**
     * 监听ws关闭事件 继承必须实现
     * TCP客户端连接关闭后，在worker进程中回调此函数。函数原型：
     *   function onClose(swoole_server $server, int $fd, int $reactorId);
     *   $server 是swoole_server对象
     *   $fd 是连接的文件描述符
     *   $reactorId 来自那个reactor线程
     */
    public function onClose($swoole, $fd)
    {
        $this->unBind($fd);
        foreach ($this->timer[$fd] as $_k => $timerid) {
            swoole_timer_clear($timerid);
        }
        unset($this->timer[$fd]);
        echo "connection close: " . $fd;
    }


    /************************具体业务实现流程**************************************/
    public function initDb()
    {
        $conn = ConnectMysqli::getIntance([
            'host' => '192.168.1.82',
            'port' => '3306',
            'user' => 'root',
            'pass' => '123456',
            'db' => 'demo',
            'charset' => 'utf8'
        ]);
        $this->conn = $conn;

        $redis = Redis::getInstance([
            'host' => '127.0.0.1',
            'port' => 6379
        ], 0);
        $this->redis = $redis;
    }

    public function add($fid, $tid, $content)
    {
        $sql = "insert into tb_msg (fid,tid,content) values ($fid,$tid,'$content')";
        if ($this->conn->query($sql)) {
            $id = $this->conn->getInsertid();
            $data = $this->loadHistory($fid, $tid, $id);
            return $data;
        }
    }

    public function bind($uid, $fd)
    {

        $sql = "delete from tb_fd where uid=$uid";
        $this->conn->query($sql);

        $sql = "insert into tb_fd (uid,fd) values ('$uid',$fd)";
        if ($this->conn->query($sql)) {
            return true;
        }
    }

    public function getFd($uid)
    {
        $sql = "select * from tb_fd where uid='$uid' limit 1";
        echo $sql . PHP_EOL;
        $row = "";
        if ($query = $this->conn->getRow($sql)) {
            $data = $query;
            $row = $data['fd'];
        }
        return $row;
    }

    public function unBind($fd, $uid = null)
    {
        if ($uid) {
            $sql = "delete from tb_fd where uid=$uid";
        } else {
            $sql = "delete from tb_fd where fd=$fd";
        }
        if ($this->conn->query($sql)) {
            return true;
        }
    }

    public function loadHistory($fid, $tid, $id = null)
    {
        $and = $id ? " and id=$id" : '';
        $sql = "select * from tb_msg where ((fid=$fid and tid = $tid) or (tid=$fid and fid = $tid))" . $and;
        $data = [];
        if ($query = $this->conn->getAll($sql)) {
            $data = $query;
        }
        return $data;
    }

    /**
     * 字节转换字符串
     * @param $data
     * @return string
     */
    private function byteToString($data)
    {
        return implode('', array_map(function ($item) {
            return chr($item);
        }, $data));
    }

    /**
     * 字符串转换字节
     * @param $string
     * @return string
     */
    private function stringToByte($string)
    {
        $string = (string)$string;
        $bytes = [];
        for ($i = 0; $i < strlen($string); $i++) {
            $bytes[] = ord($string[$i]);
        }
        return implode("", $bytes);
    }
}