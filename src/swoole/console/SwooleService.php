<?php
/**
 * Created by PhpStorm.
 * User: liumingyu
 * Date: 2021/1/12
 * Time: 3:18 PM
 */
namespace tebie6\swoole\console;

class SwooleService
{
    public $host = "0.0.0.0";

    public $port = 9998;

    public $mode = SWOOLE_PROCESS;

    public $socketType = SWOOLE_TCP;

    public $rootDir = "";

    public $type = "advanced";

    public $app = "frontend";//如果type为basic,这里默认为空

    public $web = "web";

    public $debug = true;//是否开启debug

    public $env = 'dev';//环境，dev或者prod...

    public $gcSessionInterval = 60000;

    public $swooleConfig = [
        // 心跳检测 每隔多少秒，遍历一遍所有的连接
//        'heartbeat_check_interval' => 2,
        // 心跳检测 最大闲置时间，超时触发close并关闭  默认为heartbeat_check_interval的2倍，两倍是容错机制，多一点是网络延迟的弥补
//        'heartbeat_idle_time' => 5,
        'reactor_num' => 2,
        'worker_num' => 4,
        'task_worker_num' => 4,
        'daemonize' => false,
        'log_file' => __DIR__ . '/../logs/swoole.log',
        'log_level' => 0,
        'pid_file' => __DIR__ . '/../logs/server.pid',
    ];

    public $project = [
        'server'=>[
            'tebie6\swoole\project\ChatWebsocketServer',
        ],
    ];

    public function start()
    {
        if( $this->getPid() !== false ){
            $this->stderr("server already  started");
            exit(1);
        }
        $projectServer=$this->project['server'][0];
        if($projectServer && class_exists($projectServer)){
            $server = new $projectServer($this->host, $this->port, $this->mode, $this->socketType, $this->swooleConfig, ['gcSessionInterval'=>$this->gcSessionInterval]);
        }else{
            throw new \Exception('找不到'.$projectServer.'请检查路径是否正确');
        }
        $this->stdout("server is running, listening {$this->host}:{$this->port}" . PHP_EOL);
        $server->run();
    }

    public function stop()
    {
        $this->sendSignal(SIGTERM);
        $this->stdout("server is stopped, stop listening {$this->host}:{$this->port}" . PHP_EOL);
    }

    public function reloadTask()
    {
        $this->sendSignal(SIGUSR2);
    }

    public function restart()
    {
        $this->sendSignal(SIGTERM);
        $time = 0;
        while (posix_getpgid($this->getPid()) && $time <= 10) {
            usleep(100000);
            $time++;
        }
        if ($time > 100) {
            $this->stderr("Server stopped timeout" . PHP_EOL);
            exit(1);
        }
        if( $this->getPid() === false ){
            $this->stdout("Server is stopped success" . PHP_EOL);
        }else{
            $this->stderr("Server stopped error, please handle kill process" . PHP_EOL);
        }
        $this->actionStart();
    }

    private function sendSignal($sig)
    {
        if ($pid = $this->getPid()) {
            posix_kill($pid, $sig);
        } else {
            $this->stdout("server is not running!" . PHP_EOL);
            exit(1);
        }
    }


    private function getPid()
    {
        $pid_file = $this->swooleConfig['pid_file'];
        if (file_exists($pid_file)) {
            $pid = file_get_contents($pid_file);
            if (posix_getpgid($pid)) {
                return $pid;
            } else {
                unlink($pid_file);
            }
        }
        return false;
    }

    /**
     *@ 标准错误，默认情况下会发送至用户终端
     *@ php://stderr & STDERR
     *@ STDERR是一个文件句柄，等同于fopen("php://stderr", 'w')
     */
    public function stderr($str){
        fwrite(STDERR, $str.PHP_EOL);
    }

    /**
     *@ 标准输出
     *@ php://stdout & STDOUT
     *@ STDOUT是一个文件句柄，等同于fopen("php://stdout", 'w')
     */
    public function stdout($str){
        fwrite(STDOUT, $str.PHP_EOL);
    }

}