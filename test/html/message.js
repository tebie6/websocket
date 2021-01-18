function Message(url) {
    var _this = this;
    var promise = null;
    var isFunction = function (fn) {
        return typeof fn == 'function' ? true : false;
    };

    // 字符串转字节序列
    var stringToByte = function (str) {
        var byteArray = [];
        for (var i = 0; i < str.length; i++) {
            var charcode = str.charCodeAt(i);
            if (charcode < 0x80) byteArray.push(charcode);
            else if (charcode < 0x800) {
                byteArray.push(0xc0 | (charcode >> 6),
                    0x80 | (charcode & 0x3f));
            } else if (charcode < 0xd800 || charcode >= 0xe000) {
                byteArray.push(0xe0 | (charcode >> 12),
                    0x80 | ((charcode >> 6) & 0x3f),
                    0x80 | (charcode & 0x3f));
            } else {
                i++;
                charcode = 0x10000 + (((charcode & 0x3ff) << 10) |
                    (str.charCodeAt(i) & 0x3ff));
                byteArray.push(0xf0 | (charcode >> 18),
                    0x80 | ((charcode >> 12) & 0x3f),
                    0x80 | ((charcode >> 6) & 0x3f),
                    0x80 | (charcode & 0x3f));
            }
        }
        return byteArray;
    };

    // 字节序列转ASCII码
    // [0x24, 0x26, 0x28, 0x2A] ==> "$&C*"
    var byteToString = function (byteArray) {
        var str = '';
        var _arr = byteArray;

        if (typeof byteArray === 'string') {
            return byteArray;
        }
        for (var i = 0; i < _arr.length; i++) {
            var one = _arr[i].toString(2),
                v = one.match(/^1+?(?=0)/);
            if (v && one.length == 8) {
                var bytesLength = v[0].length;
                var store = _arr[i].toString(2).slice(7 - bytesLength);
                for (var st = 1; st < bytesLength; st++) {
                    store += _arr[st + i].toString(2).slice(2);
                }
                str += String.fromCharCode(parseInt(store, 2));
                i += bytesLength - 1;
            } else {
                str += String.fromCharCode(_arr[i]);
            }
        }
        return str;
    };

    // json转二进制数据, 发送消息用此函数转换
    var jsonToPack = function (json) {
        var buffer = null;
        var view = null;
        // var vHostArr = [];
        var userNameArr = [];
        var pwdArr = [];
        var msgIdArr = [];
        var bodyArr = [];
        //var vHostLen = 0;
        var userNameLen = 0;
        var pwdLen = 0;
        var tLength = 0;
        var markBit = 0;

        if (json.act == 'CONNECT') { //连接websocket
            userNameArr = stringToByte(json.username);
            pwdArr = stringToByte(json.password);
            roleArr = stringToByte(json.role || 0x00);
            userNameLen = userNameArr.length;
            pwdLen = pwdArr.length;
            tLength = userNameLen + pwdLen;
            buffer = new Buffer(tLength + 6);

            view = buffer.setUint(1, 0x01); //CONNECT指令分配1个字节
            buffer.setUint(2, userNameLen); //用户名长度分配2个字节
            userNameArr.forEach(function (v, i) {
                buffer.setUint(1, v); //用户名里每个字符的unicode码分配一个字节
            })
            buffer.setUint(2, pwdLen); //密码长度分配2个字节
            pwdArr.forEach(function (v, i) {
                buffer.setUint(1, v); //密码里每个字符的unicode码分配一个字节
            })


            var role = JSON.stringify(json.role || 0x00);
            role = role.charCodeAt(0)
            buffer.setUint(1, role); //用户角色分配一个字节，可选，默认值为0
        } else if (json.act == 'PUBLISH' || json.act == 'GROUP_PUB') { //发送消息体
            switch (json.act) {
                case 'PUBLISH':
                    markBit = 0x03;
                    break;
                case 'GROUP_PUB':
                    markBit = 0x05;
                    break;
                default:
                    break;
            }
            msgIdArr = stringToByte(json.msgId);
            bodyArr = stringToByte(JSON.stringify(json.body));
            tLength = bodyArr.length;
            buffer = new Buffer(tLength + msgIdArr.length + 7);
            view = buffer.setUint(1, markBit); //PUBLISH指令分配1个字节
            buffer.setUint(4, json.to); //目标用户id分配4个字节
            msgIdArr.forEach(function (v, i) {
                buffer.setUint(1, v); //消息id里每个字符的unicode码分配一个字节
            });
            buffer.setUint(2, tLength); //消息体长度分配2个字节
            bodyArr.forEach(function (v, i) {
                buffer.setUint(1, v); //消息体里每个字符的unicode码分配一个字节
            })
        } else if (json.act == 'PONG') { //发送心跳确认
            buffer = new Buffer(1);
            view = buffer.setUint(1, 0x08); //PONG指令分配1个字节
        } else if (json.act == 'DISCONNECT') { //主动断开连接
            buffer = new Buffer(1);
            view = buffer.setUint(1, 0x09); //DISCONNECT指令分配1个字节
        } else if (json.act == 'PUB_ACK') { //发送确认消息
            msgIdArr = stringToByte(json.msgId);
            tLength = msgIdArr.length;
            buffer = new Buffer(tLength + 1);
            view = buffer.setUint(1, 0x04); //PUB_ACK指令分配1个字节
            msgIdArr.forEach(function (v, i) {
                buffer.setUint(1, v); //消息id里每个字符的unicode码分配一个字节
            });
        }

        return view;
    };

    // 二进制数据转json, 接收消息用此函数转换
    var packTojson = function (bytePack) {
        var json = {};
        var isIE = /Trident/.test(navigator.userAgent);
        var byteArr = new Uint8Array(bytePack);
        var buffer = byteArr.buffer;
        var view = new DataView(buffer);
        if (isIE) {
            Uint8Array.prototype.slice = Array.prototype.slice;
        }
        if (byteArr[0] == 0x02) { //连接websocket成功
            if (view.getUint8(1) == 0) {
                json.act = 'CONNECT_ACK';
            }
        } else if (byteArr[0] == 0x07) { //心跳检测
            json.act = 'PING';
        } else if (byteArr[0] == 0x04 || byteArr[0] == 0x06) { //消息确认
            json.act = 'PUB_ACK';
            json.msgId = byteToString(byteArr.slice(1, 33));
        } else if (byteArr[0] == 0x03) { //消息体
            json.act = 'PUB';
            json.msgId = byteToString(byteArr.slice(1, 33));
            json.bodySize = view.getUint16(33);
            json.body = JSON.parse(byteToString(byteArr.slice(35)));
        }

        return json;
    };

    this.socket = new WebSocket(url);

    this.connect = function (data) {
        promise = new Promise(function (resolve, reject) {
            _this.socket.onopen = function () {

                _this.socket.binaryType = 'arraybuffer';
                _this.socket.send(jsonToPack(data));
                resolve();
            }
        });
    };

    this.send = function (data) {

        promise.then(function () {
            _this.socket.send(jsonToPack(data));
        });
    };

    this.close = function () {
        promise.then(function () {
            _this.socket.close();
        });
    };

    this.on = function (e, callback) {
        if (e == 'message') {
            _this.socket.onmessage = function (e) {
                isFunction(callback) ? callback(packTojson(e.data)) : null;
            }

        } else if (e == 'close') {
            _this.socket.onclose = function () {
                isFunction(callback) ? callback() : null;
            }

        } else if (e == 'error') {
            _this.socket.onerror = function () {
                isFunction(callback) ? callback() : null;
            }
        }
    }

}

function Buffer(length) {
    var _this = this;
    this.index = 0;
    this.buffer = new ArrayBuffer(length);
    this.setUint = function (byteLen, uint) {
        var length = 0;
        var view = new DataView(_this.buffer);
        if (typeof uint != 'number') {
            return uint;
        }

        if (byteLen == 1) {
            view.setUint8(_this.index, uint);
            _this.index += 1;
        } else if (byteLen == 2) {
            view.setUint16(_this.index, uint);
            _this.index += 2;
        } else if (byteLen == 4) {
            view.setUint32(_this.index, uint);
            _this.index += 4;
        }

        return view.buffer;
    }

    return {
        setUint: this.setUint
    }
}