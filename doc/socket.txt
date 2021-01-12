# 部署环境

## 一、 服务器简介
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
        
## 传输层协议采用的是websocket, websocket的消息体格式必须设置为binary
    ws://服务器地址:9095/ws
    
## 传输层的tls协议的实现，即: wss, websocket的消息体格式必须设置为binary
    wss://服务器地址:8443/wss
    
## 二、 客户端请求服务器端协议部分

### 常量说明

    -define(CONNECT,        1). %% Client request to connect to Server
    -define(CONNECT_ACK,    2). %% Server to Client: Connect acknowledgment
    
    -define(PUBLISH,        3). %% Publish message
    -define(PUB_ACK,        4). %% Publish acknowledgment
    
    -define(GROUP_PUBLISH, 5). %% Group Publish message
    -define(GROUP_PUB_ACK, 6). %% Group Publish acknowledgment
    
    // 注意新增消息类型, 支持对群内的部分用户发送消息
    -define(GROUP_PART_PUBLISH, 11). %% Group Part Publish message
    -define(GROUP_PART_PUB_ACK, 12). %% Group Part Publish acknowledgment
    
    -define(PING,           7). %% PING request
    -define(PING_RESP,      8). %% PING response
    
    -define(DISCONNECT,     9). %% Client or Server is disconnecting

### connect指令

    客户端发送到服务器端的第一个包必须是connect包, 否则连接关闭
    <<?CONNECT:8,
            UsernameLen:16, UserName/binary,
            PasswordLen:16, Password/binary,
            Role:8>>
    变量解释:
    1. ?CONNECT代表connect，占1个byte, 值为: 0x01
    2. UsernameLen 2个byte 表示后续的Username的字节数，无符号整数
    3. Username [byte], 表示对应的Username的字节流
    4. PasswordLen 2个byte 表示后续的Password的字节数，无符号整数
    5. Role, [可选值] 占1个byte, 表示用户角色，用来做后端校验时的用户角色区分, 默认为0
    
    返回值：
       <<?CONNECT_ACK:8, Code:8>>
       1. ?CONNECT_ACK, 占1个byte，值参考[常量说明], 值为: 0x02 
       2. Code值为返回码，占1个byte值为：
            0: Connection accepted, 连接成功
            1: Connection dup, 重复建立连接
            3: Username or password is error, 用户名或密码错误 
            
    注明：在返回非0的返回值后，服务器端会关闭连接
            
    举列：
    username: "test"      -> length: 4
    password: "123456"    -> length: 6， 注意：类型为字符串
    role: 1
    
    打包后的格式为, 以下表示的都是byte[]数组格式
    |0x01    |0x00|0x04   |"test".getBytes()  |0x00|0x06       |"123456".getBytes() | 0x01
    --cmd--  --UserLen--  ----Username----    --PasswordLen--  --Password bytes---   -Role-
                    
### 发送消息
    <<?PUBLISH:8, ToUid:32, MsgId:32/binary, PayloadLen:16, Payload/bianry>>
    
    变量解释：
    1. ?PUBLISH, 占1个byte，值为：0x03
    2. ToUid, 占4个byte; 32位整数，目标用户id
    2. MsgId, 32位字符串, uuid，用来做消息确认，服务器消息后，会将相应的MsgId返回个客户端
    3. PayloadLen, 占2个byte，标识后续的payload的字节数
    4. Payload [byte], 表示对应的topic字节流 
    
    返回值：
    <<?PUB_ACK:8, MsgId:32/binary>>;
    1. ?PUB_ACK，占1个byte， 值为: 0x04
    2. MsgId 32位字符串, uuid，为请求包中的MsgId的值
    
    举列：
    ToUid: 1234
    MsgId: "550e8400e29b41d4a716446655440000"
    Payload: "hello world"
    PayloadLen: Payload.getBytes().length
    
    打包后的格式为, 以下表示的都是byte[]数组格式:
    请求包：
    |0x03 |0x00|0x00|0x04|0xd2   |"550e8400e29b41d4a716446655440000".getBytes()  |0x00|0x0b|     "hello world".getBytes()
    命令   -----ToUid---------                 ------MsgId-----                  --PayloadLen--  ------Payload bytes------ 
    
    响应包：
    |0x04    |"550e8400e29b41d4a716446655440000".getBytes()
    -cmd-           ---------MsgId------
     
### 发送群消息
    <<?GROUP_PUBLISH:8, GroupId:32, MsgId:32/binary, PayloadLen:16, Payload/bianry>>
    
    变量解释：
    1. ?GROUP_PUBLISH, 占1个byte，值为：0x05
    2. GroupId, 占4个byte; 32位整数，群id
    2. MsgId, 32位字符串, uuid，用来做消息确认，服务器消息后，会将相应的MsgId返回个客户端
    3. PayloadLen, 占2个byte，标识后续的payload的字节数
    4. Payload [byte], 表示对应的topic字节流 
    
    返回值：
    <<?GROUP_PUB_ACK:8, MsgId:32/binary>>;
    1. ?GROUP_PUB_ACK，占1个byte， 值为: 0x06
    2. MsgId 32位字符串, uuid，为请求包中的MsgId的值
    
    举列：
    GroupId: 1234
    MsgId: "550e8400e29b41d4a716446655440000"
    Payload: "hello world"
    PayloadLen: Payload.getBytes().length
    
    打包后的格式为, 以下表示的都是byte[]数组格式:
    请求包：
    |0x05 |0x00|0x00|0x04|0xd2  |"550e8400e29b41d4a716446655440000".getBytes()  |0x00|0x0b|     "hello world".getBytes()
    命令   -----GroupId---------                          ------MsgId-----      --PayloadLen--  ------Payload bytes------ 
    
    响应包：
    |0x06  |"550e8400e29b41d4a716446655440000".getBytes() 
    -cmd-    ---------MsgId------
    
### 发送群消息
    <<?GROUP_PART_PUBLISH:8, GroupId:32, MsgId:32/binary, ToUserNum:16, ToUid:32..., PayloadLen:16, Payload/bianry>>
   
    变量解释：
    1. ?GROUP_PART_PUBLISH, 占1个byte，值为：0x0b
    2. GroupId, 占4个byte; 32位整数，群id
    3. MsgId, 32位字符串, uuid，用来做消息确认，服务器消息后，会将相应的MsgId返回个客户端
    4. ToUserNum, 占2个byte; 16位整数，单独发送到群内的用户数
    5. ToUid..., 占4个byte; 32位整数，目标用户id, ToUid的个数为ToUserNum对应的值
    6. PayloadLen, 占2个byte，标识后续的payload的字节数
    7. Payload [byte], 表示对应的topic字节流 
    
    返回值：
    <<?GROUP_PART_PUB_ACK:8, MsgId:32/binary>>;
    1. ?GROUP_PUB_ACK，占1个byte， 值为: 0x0c
    2. MsgId 32位字符串, uuid，为请求包中的MsgId的值
    
    举列：
    GroupId: 1234
    MsgId: "550e8400e29b41d4a716446655440000"
    ToUserNum: 2,
    ToUser...: [1234, 2345]
    Payload: "hello world"
    PayloadLen: Payload.getBytes().length
    
    打包后的格式为, 以下表示的都是byte[]数组格式:
    请求包：
    |0x0b |0x00|0x00|0x04|0xd2  |"550e8400e29b41d4a716446655440000".getBytes() | 0x02 |         |0x00|0x00|0x04|0xd2 | |0x00|0x00|0x04|0xd3 |  |0x00|0x0b|     "hello world".getBytes()
    命令   -----GroupId---------                          ------MsgId-----      --ToUserNum--         --User1--               ---User2---       --PayloadLen--  ------Payload bytes------ 
   
    响应包：
    |0x0c  |"550e8400e29b41d4a716446655440000".getBytes() 
    -cmd-    ---------MsgId------ 

### 断开连接命令
    客户端自动断开连接时发送的命令
    
    <<?DISCONNECT:8>>
    变量解释：
       1. ?DISCONNECT, 占1个byte，值为：0x09
       
    返回值：
        该命令无返回值

## 三、服务器端推送消息到客户端协议

### 心跳保存
    服务器会自动发送心跳包来检测客户端是否存活，如果在规定的时间内没有收到客户端的心跳服务，服务器会主动端口客户端的连接
    
    <<?PING:8>>
    变量解释：
       1. ?PING, 占1个byte，值为：0x07
       
    客户端回复：
    <<?PING_RESP:8>>
    变量解释：
       1. ?PING_RESP, 占1个byte，值为：0x08
       
### 消息推送
    服务器端会推送消息到客户端
    <<?PUBLISH:8,
            MsgId:32/binary,
            PayloadLen:16, Payload/binary>>
    变量解释：
        1. ?PUBLISH, 占1个byte，值为：0x03
        2. MsgId, 32位字符串, uuid, 消息标识, 用来做消息确认
        3. PayloadLen, 占2个byte，标识后续的Payload的字节数
        4. Payload [byte], 表示对应的Payload字节流
    客户端回复：
    <<?PUB_ACK:8, MsgId:32/binary>>
    变量解释：
        1. ?PUB_ACK, 占1个byte，值为: 0x04
        2. MsgId, 32位字符串, uuid, 消息标识, 用来做消息确认

    特殊说明：
        1. 服务器在未收到消息确认时，会每间隔2s重复推送一次消息，最多重试3次；3次之后任然未收到回复，服务器将断开客户端的连接
        2. 客户端需要处理由于网络延迟等原因导致的消息重复获取，可以根据消息的MsgId来排重
        
## 四、 tcp接口(所有版本都可用)
    1. tcp服务为tcp长连接服务，即在一个连接上，可以进行多次通讯
    2. 服务开放端口为：19095
    
    协议格式: 
        请求消息体协议为：[4字节消息长度] + [请求消息字节数]
        响应消息体协议为：[4字节消息长度] + [响应消息字节数]
        
### 发布消息
     <<?PUBLISH:8, ToUid:32, MsgId:32/binary, PayloadLen:16, Payload/bianry>>
     
     变量解释：
     1. ?PUBLISH, 占1个byte，值为：0x03
     2. ToUid, 占4个byte; 32位整数，目标用户id
     2. MsgId, 32位字符串, uuid，用来做消息确认，服务器消息后，会将相应的MsgId返回个客户端
     3. PayloadLen, 占2个byte，标识后续的payload的字节数
     4. Payload [byte], 表示对应的topic字节流 
     
     返回值：
     <<?PUB_ACK:8, MsgId:32>>;
     1. ?PUB_ACK，占1个byte， 值为: 0x04
     2. MsgId 2位字符串, uuid，为请求包中的MsgId的值
     
     举列：
     ToUid: 1234
     MsgId: "550e8400e29b41d4a716446655440000"

     Payload: "hello world"
     PayloadLen: Payload.getBytes().length
     
     打包后的格式为, 以下表示的都是byte[]数组格式:
     请求包：
     |0x03 |0x00|0x00|0x04|0xd2  |"550e8400e29b41d4a716446655440000".getBytes()  |0x00|0x0b|     "hello world".getBytes()
     命令   -----ToUid---------                             ------MsgId-----      --PayloadLen--  ------Payload bytes------ 
     
     响应包：
     |0x04    |"550e8400e29b41d4a716446655440000".getBytes()
     -cmd-          ---------MsgId------

## 五、用户登录时的校验
    用户在建立到im服务器的连接时，校验方式采用：用户名 + 密码；IM服务器会把用户和密码通过post的方式提交到用户中心的api进行校验
    IM通过用户中心的校验结果决定是否需要client的的连接
    
    请求方式：
    http://auth.xx.com/auth (配置文件配置)
    headers:
        [ {"Accept", "application/json"},
          {"Content-Type", "application/x-www-form-urlencoded;charset=utf-8"}]
    body:
        username=$username&password=$password
        
    响应为json格式：
        通过：
           {"result": "pass", "uid": Int}
        不通过:
           {"result": "denied"}

## 六、消息群发
    群内成员是通过读取本机服务器上的redis中的数据获取的，因此建立群组的时候需要将群的用户id信息写入对应的redis中
    
    redis服务器的配置, 注意: database的值为1
    {group_pool, {
        %% size: 连接进程数, max_overflow: 最大连接数, worker_module: 固定值，当前为eredis模块
        [{size, 10}, {max_overflow, 20},  {worker_module, eredis}],
        %% host: redis服务域名或ip, port: 端口
        [{host, "localhost"}, {port, 6379}, {database, 1}]}
    }
    
    group对应的key约定, 类型为: 集合
    key = "im_group:" ++ group_id, 如group_id的值12，则对应的key值为: "im_group:12"
    
    注意：
        群内成员发生变化的时候，需要业务端维护对应group中成员uid的修改
    