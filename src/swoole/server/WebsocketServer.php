<?php
namespace tebie6\swoole\server;

use swoole_websocket_server;
/**
 * 单独的websocket服务器 
 */
abstract class WebsocketServer
{
    public $swoole;
    public $webRoot;
    public $config = [];
    public $runApp;

    public function __construct($host, $port, $mode, $socketType, $swooleConfig=[], $config=[])
    {
        $this->swoole = new swoole_websocket_server($host, $port, $mode, $socketType);
        if( !empty($this->config) ) $this->config = array_merge($this->config, $config);
        $this->swoole->set($swooleConfig);
        //task事件
        $this->swoole->on("task",[$this,'onTask']); 
        $this->swoole->on("finish",[$this,'onFinish']); 
        //worker
        $this->swoole->on('WorkerStart', [$this, 'onWorkerStart']);
        //websocket
        $this->swoole->on("open",[$this,'onOpen']); 
        $this->swoole->on("message",[$this,'onMessage']);
        $this->swoole->on("close",[$this,'onClose']);

    }

    /**
     * 监听task事件
     */
    public function onTask($serv,$taskId,$workderId,$data){

    }

    /**
     * 监听task关闭事件  
     */
    public function onFinish($serv,$taskId,$data){
    }

    /**
     * Worker子进程启动时的回调函数，每个子进程启动时都会执行。
     */
    public function onWorkerStart( $serv , $worker_id) {

    }
    /**
     * ws事件 继承根据业务重写
     */
    abstract protected function onOpen($swoole,$request);
    abstract protected function onMessage($swoole,$frame);
    abstract protected function onClose($swoole,$fd);
    
    /**
     * 启动server
     */
    public function run(){
        $this->swoole->start();
    }
    
    
}