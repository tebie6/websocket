# websocket test
基于swoole websocket 的聊天服务 【仅限学习使用】

引用 composer require tebie6/websocket

    具备以下特点
        1. 自定义二进制协议
        2. 支持单聊和群聊 
        3. 基于用户名+密码的校验机制 
        4. 支持消息确认机制
        5. 支持心跳检查，心跳包是服务器自动发送的，服务器在一定时间内未收到客户端的心跳回复，会断开客户端的连接
        8. 支持离线消息
    交互特定
        1. 异步全双工 
        2. 低延迟软实时


### 目录结构
      
     ├── README.md
     ├── composer.json
     ├── composer.lock
     ├── doc                                      ## 文档
     │   └── socket.txt
     ├── src                                      ## 核心代码
     │   └── swoole
     │       ├── console
     │       │   └── SwooleService.php            ## 脚本继承类
     │       ├── logs                             ## 日志目录
     │       │   └── swoole.log
     │       ├── model                            ## model类
     │       │   ├── Chat.php
     │       │   ├── ConnectMysqli.php
     │       │   ├── OCurl.php
     │       │   └── OUtils.php
     │       ├── project                          ## 项目业务文件
     │       │   ├── ChatWebsocketServer.php
     │       │   └── chat.sql
     │       └── server                           ## 项目继承类
     │           └── WebsocketServer.php
     └── test                                     ## 测试样例
         ├── html                                 ## 样例前段代码
         │   ├── chat_client_websocket.html       
         │   ├── chat_client_websocket2.html
         │   ├── jquery.json.js
         │   └── message.js
         └── websocket.php                        ## 入口文件
