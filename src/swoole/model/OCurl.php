<?php
/**
 * Curl类
 * OCurl.class.php
 * @version 2015-05-30
 */
namespace tebie6\swoole\model;

class OCurl {

	/**
	 * @var HTTP方法
	 */
	public static $method = 'GET';
	/**
	 * @var 请求头
	 */
	public static $requestHeader;
	/**
	 * @var 响应头
	 */
	public static $responseHeader;
	/**
	 * @var 请求cookie
	 */
	public static $requestCookie;
	/**
	 * @var 响应cookie
	 */
	public static $responseCookie;
	/**
	 * @var 请求数据
	 */
	public static $requestData;
	/**
	 * @var 响应数据
	 */
	public static $responseData;
	/**
	 * @var 请求URL
	 */
	public static $url;
	/**
	 * @var 是否ajax请求
	 */
	public static $ajax;	
	/**
	 * @var 是否启用毫秒超时
	 */
	public static $needMs = false;
	/**
	 * @var 尝试连接等待的时间（秒）
	 */
	public static $connTimeout;
	/**
	 * @var 允许执行的最长时间（秒）
	 */
	public static $timeout;
	/**
	 * @var 尝试连接等待的时间（毫秒）
	 */
	public static $connTimeoutMs;
	/**
	 * @var 允许执行的最长时间（毫秒）
	 */
	public static $timeoutMs;
	/**
	 * @var Content-type
	 */
	public static $ContentType;
	/**
	 * @var Authorization
	 */
	public static $Authorization;
	/**
	 * @var cookie文件路径
	 */
	public static $cookieFile = null;
	/**
	 * @var 代理配置
	 */
	public static $proxy = null;
	/**
	 * @var 字符编码
	 */
	public static $charset = 'utf-8';
	/**
	 * @var 请求来源
	 */
	public static $referer;
	/**
	 * @var 请求Ip
	 */
	public static $ip;
	/**
	 * @var 请求失败后重新请求次数
	 */
	public static $frequency = 1;
	/**
	 * @var 最大请求失败后重新请求次数
	 */
	public static $maxFrequency = 3;
	/**
	 * @var 响应错误编号
	 */
	public static $errNo = 0;
	/**
	 * @var 响应错误字符串
	 */
	public static $errStr;
	/**
	 * @var Curl信息
	 */
	public static $curlInfo;
	/**
	 * @var Host信息
	 */
	public static $host;
	/**
	 * @var userAgent
	 */
	public static $userAgent;
	/**
	 * @var Accept
	 */
	public static $Accept;
	/**
	 * @var sslKey
	 */
	public static $sslCer;
	/**
	 * @var sslKey
	 */
	public static $sslKey;
	
	
    /**
     * 设置传递参数
     * @param array $args
     * @return boolean
     */
    public static function setOptions($args = array()) {
    	if(empty($args) || !is_array($args)) return false;
    	self::$frequency     = isset($args['freq'])   		 ? $args['freq']		  : 1;
    	self::$url           = isset($args['url'])    		 ? $args['url']     	  : '';
    	self::$requestData   = isset($args['data'])    		 ? $args['data']    	  : '';
    	self::$ajax          = isset($args['ajax'])    		 ? $args['ajax']    	  : '';
    	self::$referer       = isset($args['referer']) 		 ? $args['referer'] 	  : '';
    	self::$ip            = isset($args['ip'])      		 ? $args['ip']      	  : '';
    	self::$proxy         = isset($args['proxy'])   		 ? $args['proxy']  		  : null;		
		self::$needMs        = isset($args['need_ms']) 	 	 ? $args['need_ms']    	  : false;
    	self::$connTimeout   = isset($args['conntimeout'])   ? $args['conntimeout']   : 3;
		self::$timeout       = isset($args['timeout'])  	 ? $args['timeout']  	  : 5;
		self::$connTimeoutMs = isset($args['conntimeout_ms'])? $args['conntimeout_ms']: 3000;
		self::$timeoutMs     = isset($args['timeout_ms'])  	 ? $args['timeout_ms']    : 5000;
    	self::$charset       = isset($args['charset']) 		 ? $args['charset'] 	  : 'utf-8';
    	self::$cookieFile    = isset($args['cookiefile'])    ? $args['cookiefile'] 	  : null;
    	self::$ContentType   = isset($args['content-type'])  ? $args['content-type']  : '';
    	self::$Authorization = isset($args['authorization']) ? $args['authorization'] : '';
    	self::$host          = isset($args['host'])          ? $args['host']          : '';
		self::$userAgent     = isset($args['useragent'])     ? $args['useragent']     : (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '');
		self::$Accept        = isset($args['accept'])        ? $args['accept']        : '';
		self::$sslCer        = isset($args['ssl_cer'])        ? $args['ssl_cer']      : '';
		self::$sslKey        = isset($args['ssl_key'])        ? $args['ssl_key']      : '';
		
		
    	if(isset($args['cookie'])) {
    		self::setCookie($args['cookie']);
    	}
    	//检测是否含有上传文件
    	if (isset($args['files']) && is_array($args['files'])) {
    		foreach ($args['files'] as $k => $v) {
    			$args['files'][$k] = '@' . $v;
    		}
			if(!is_array(self::$requestData)) {
				parse_str(self::$requestData, self::$requestData);
			} 
    		self::$requestData = self::$requestData + $args['files'];
    	}
    }
    
    
    /**
     * Get方法请求
     * @param array $args
     * @return boolean
     */
    public static function get($args = array()) {
    	self::$method = 'GET';
    	self::setOptions($args);
    	self::$ContentType = self::$ContentType ? : '';
    	
    	$requestHeader = array();
    	if(!empty($args['head'])) {
    	    $requestHeader = array_merge($requestHeader,$args['head']);
    	}
    	if(!empty(self::$ContentType)) {
    		$requestHeader = array_merge($requestHeader,array(
    				'Content-Type: '.self::$ContentType,
    		));
    	}
    	if(!empty(self::$ip)) {
    		$requestHeader = array_merge($requestHeader,array(
    				'CLIENT-IP: '.self::$ip,
    				'X-FORWARDED-FOR: '.self::$ip,
    		));
    	}
    	if(!empty(self::$ajax)) {
    		//$headerOptions['X-Requested-With']  = 'XMLHttpRequest';
    		$requestHeader = array_merge($requestHeader,array(
    				'X-Requested-With: XMLHttpRequest',
    		));
    	}
    	if(!empty(self::$Authorization)) {
    		$requestHeader = array_merge($requestHeader,array(
    				'Authorization: '.self::$Authorization,
    		));
    	}
    	if(!empty(self::$host)) {
    	    $requestHeader = array_merge($requestHeader,array(
    	        'Host: '.self::$host,
    	    ));
    	}
    	if(!empty(self::$Accept)) {
    	    $requestHeader = array_merge($requestHeader,array(
    	        'Accept: '.self::$Accept,
    	    ));
    	}
    	
    	
    	self::setHeader($requestHeader);
    	return self::execute();
    }
    
    
    
    /**
     * Post方法请求
     * @param array $args
     * @return boolean
     */
    public static function post($args = array()) {
		self::$method = 'POST';
		self::setOptions($args);
		self::$ContentType = self::$ContentType ? : 'application/x-www-form-urlencoded;charset='.self::$charset;
		
		$requestHeader = array("Expect:");
		if(!empty($args['head'])) {
		    $requestHeader = array_merge($requestHeader,$args['head']);
		}
		if(!empty(self::$ContentType)) {
			$requestHeader = array_merge($requestHeader,array(
					'Content-Type: '.self::$ContentType,
			));
		}
		if(!empty(self::$ip)) {
			$requestHeader = array_merge($requestHeader,array(
					'CLIENT-IP: '.self::$ip,
					'X-FORWARDED-FOR: '.self::$ip,
					self::$ContentType,
					'Expect:'
			));
		}
		if(!empty(self::$ajax)) {
			$requestHeader = array_merge($requestHeader,array(
					'X-Requested-With: XMLHttpRequest',
			));
		}
		if(!empty(self::$Authorization)) {
			$requestHeader = array_merge($requestHeader,array(
					'Authorization: '.self::$Authorization,
			));
		}
		if(!empty(self::$host)) {
		    $requestHeader = array_merge($requestHeader,array(
		        'Host: '.self::$host,
		    ));
		}
		if(!empty(self::$Accept)) {
		    $requestHeader = array_merge($requestHeader,array(
		        'Accept: '.self::$Accept,
		    ));
		}
		
		
		self::setHeader($requestHeader);

        return self::execute();
    }
	
	
	/**
     * PUT方法请求
     * @param array $args
     * @return boolean
     */
    public static function put($args = array()) {
    	self::$method = 'PUT';
    	self::setOptions($args);
    	self::$ContentType = self::$ContentType ? : 'application/x-www-form-urlencoded;charset='.self::$charset;
    	
    	$requestHeader = array("Expect:");
    	if(!empty($args['head'])) {
    	    $requestHeader = array_merge($requestHeader,$args['head']);
    	}
    	if(!empty(self::$ContentType)) {
    		$requestHeader = array_merge($requestHeader,array(
    				'Content-Type: '.self::$ContentType,
    		));
    	}
    	if(!empty(self::$ip)) {
    		$requestHeader = array_merge($requestHeader,array(
    				'CLIENT-IP: '.self::$ip,
    				'X-FORWARDED-FOR: '.self::$ip,
    				self::$ContentType,
    				'Expect:'
    		));
    	}
    	if(!empty(self::$ajax)) {
    		$requestHeader = array_merge($requestHeader,array(
    				'X-Requested-With: XMLHttpRequest',
    		));
    	}
    	if(!empty(self::$Authorization)) {
    		$requestHeader = array_merge($requestHeader,array(
    				'Authorization: '.self::$Authorization,
    		));
    	}
    	if(!empty(self::$host)) {
    	    $requestHeader = array_merge($requestHeader,array(
    	        'Host: '.self::$host,
    	    ));
    	}
    	if(!empty(self::$Accept)) {
    	    $requestHeader = array_merge($requestHeader,array(
    	        'Accept: '.self::$Accept,
    	    ));
    	}
    	
    	
    	self::setHeader($requestHeader);
    	return self::execute();
    }
    
    
    /**
     * DELETE方法请求
     * @param array $args
     * @return boolean
     */
    public static function delete($args = array()) {
    	self::$method = 'DELETE';
    	self::setOptions($args);
    	self::$ContentType = self::$ContentType ? : 'application/x-www-form-urlencoded;charset='.self::$charset;
    
    	$requestHeader = array("Expect:");
    	if(!empty($args['head'])) {
    	    $requestHeader = array_merge($requestHeader,$args['head']);
    	}
    	if(!empty(self::$ContentType)) {
    		$requestHeader = array_merge($requestHeader,array(
    				'Content-Type: '.self::$ContentType,
    		));
    	}
    	if(!empty(self::$ip)) {
    		$requestHeader = array_merge($requestHeader,array(
    				'CLIENT-IP: '.self::$ip,
    				'X-FORWARDED-FOR: '.self::$ip,
    				self::$ContentType,
    				'Expect:'
    		));
    	}
    	if(!empty(self::$ajax)) {
    		$requestHeader = array_merge($requestHeader,array(
    				'X-Requested-With: XMLHttpRequest',
    		));
    	}
    	if(!empty(self::$Authorization)) {
    		$requestHeader = array_merge($requestHeader,array(
    				'Authorization: '.self::$Authorization,
    		));
    	}
    	if(!empty(self::$host)) {
    	    $requestHeader = array_merge($requestHeader,array(
    	        'Host: '.self::$host,
    	    ));
    	}
    	if(!empty(self::$Accept)) {
    	    $requestHeader = array_merge($requestHeader,array(
    	        'Accept: '.self::$Accept,
    	    ));
    	}
    	
    	self::setHeader($requestHeader);
    	return self::execute();
    }
    
	
    
    /**
     * Post上传
     * @param array $args
     * @return boolean
     */
    public static function upload($args = array()) {
		self::$method = 'POST';
		self::setOptions($args);
    	self::$ContentType = self::$ContentType ? : "multipart/form-data;";
    	
    	$requestHeader = array("Expect:");
    	if(!empty($args['head'])) {
    	    $requestHeader = array_merge($requestHeader,$args['head']);
    	}
    	if(!empty(self::$ContentType)) {
    		$requestHeader = array_merge($requestHeader,array(
    				'Content-Type: '.self::$ContentType,
    		));
    	}
    	if(!empty(self::$ip)) {
    		$requestHeader = array_merge($requestHeader,array(
    				'CLIENT-IP: '.self::$ip,
    				'X-FORWARDED-FOR: '.self::$ip,
    				self::$ContentType,
    				'Expect:'
    		));
    	}
    	if(!empty(self::$ajax)) {
    		$requestHeader = array_merge($requestHeader,array(
    				'X-Requested-With: XMLHttpRequest',
    		));
    	}
    	if(!empty(self::$Authorization)) {
    		$requestHeader = array_merge($requestHeader,array(
    				'Authorization:'.self::$Authorization,
    		));
    	}
    	if(!empty(self::$host)) {
    	    $requestHeader = array_merge($requestHeader,array(
    	        'Host: '.self::$host,
    	    ));
    	}
    	if(!empty(self::$Accept)) {
    	    $requestHeader = array_merge($requestHeader,array(
    	        'Accept: '.self::$Accept,
    	    ));
    	}
    	
    	
    	self::setHeader($requestHeader);
		return self::execute();
    }
    
    /**
     * 请求
     * @return boolean
     */
    public static function request() {
    	//参数分析
    	if (!self::$url) {
    		return false;
    	}


		//读取网址内容
    	$ch = curl_init();
    	
    	//设置代理
    	if (!is_null(self::$proxy)) {
    		curl_setopt ($ch, CURLOPT_PROXY, self::$proxy);
    	}

    	if (self::$method == 'GET' && !empty(self::$requestData)) {
    		if (!is_array(self::$requestData)) self::$requestData = (array)self::$requestData;
    		//分析网址中的参数
    		$paramUrl = http_build_query(self::$requestData, '', '&');
    		$extStr   = (strpos(self::$url, '?') !== false) ? '&' : '?';
    		self::$url      = self::$url . (($paramUrl) ? $extStr . $paramUrl : '');
    	} 
    	
    	curl_setopt($ch, CURLOPT_URL, self::$url);

		//分析是否开启SSL加密
    	$ssl = substr(self::$url, 0, 8) == 'https://' ? true : false;
    	if ($ssl) {
    		// 对认证证书来源的检查
    		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    		// 从证书中检查SSL加密算法是否存在
    		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);
    	}
    	
    	// 证书文件设置
    	if (!empty(self::$sslCer)) {
    	    if (file_exists(self::$sslCer)) {
    	        curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
    	        curl_setopt($ch, CURLOPT_SSLCERT, self::$sslCer);
    	    }
    	}
    	// 证书文件设置
    	if (!empty(self::$sslKey)) {
    	    if (file_exists(self::$sslKey)) {
    	        curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
    	        curl_setopt($ch, CURLOPT_SSLKEY, self::$sslKey);
    	    }
    	}
    	
    	//cookie file
    	self::$cookieFile && self::$cookieFile = CACHE_PATH . 'curl/' . md5(self::$url) . '.txt';
    	//cookie设置
    	self::$cookieFile && curl_setopt($ch, CURLOPT_COOKIEJAR, self::$cookieFile);
    	self::$cookieFile && curl_setopt($ch, CURLOPT_COOKIEFILE, self::$cookieFile);
    	self::$requestCookie && curl_setopt($ch,CURLOPT_COOKIE,self::$requestCookie);
    	
    	//设置浏览器
    	curl_setopt($ch, CURLOPT_USERAGENT, self::$userAgent);
    	//是否显示头部信息
    	curl_setopt($ch, CURLOPT_HEADER, true);
    	//是否不显示body信息
    	curl_setopt($ch, CURLOPT_NOBODY,false);

		//自动设置Referer
    	if(self::$referer) {
    		curl_setopt($ch, CURLOPT_REFERER, self::$referer);
    	} else {
    		curl_setopt($ch, CURLOPT_AUTOREFERER,true);
    	}
    	
    	if (self::$method == 'POST' || self::$method == 'PUT') {

    		//curl_setopt ( $ch, CURLOPT_CUSTOMREQUEST, self::$method );
    		if (self::$method == 'PUT') {
    			//curl_setopt ($ch, CURLOPT_PUT , true);
    			curl_setopt ( $ch, CURLOPT_CUSTOMREQUEST, 'PUT' );
    		}
    		//Post提交的数据包
    		$postBodyString = '';
    		if (is_array(self::$requestData) && 0 < count(self::$requestData)) {
    			$postMultipart = false;
    			foreach (self::$requestData as $_k => $_v) {
    				if ("@" != substr($_v, 0, 1)) //判断是不是文件上传
    				{
    					$postBodyString .= "$_k=" . urlencode($_v) . "&";
    				} else //文件上传/传递数组默认用multipart/form-data，否则用www-form-urlencoded
    				{
    					$postMultipart = true;
    				}
    			}
    			unset ($_k, $_v);
    			//发送一个常规的Post请求
    			curl_setopt($ch, CURLOPT_POST, true);
    			if ($postMultipart) {
    				curl_setopt($ch, CURLOPT_POSTFIELDS, self::$requestData);
    			} else {
    				curl_setopt($ch, CURLOPT_POSTFIELDS, substr($postBodyString, 0, -1));
    			}
    			
    		} else {
    			curl_setopt($ch, CURLOPT_POSTFIELDS, self::$requestData);
    		}
		} elseif (self::$method == 'DELETE') {
    		curl_setopt ( $ch, CURLOPT_CUSTOMREQUEST, 'DELETE' );
    	}

		//使用自动跳转
    	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    	//true如果成功只将结果返回，不自动输出任何内容。如果失败返回FALSE;false如果成功只返回TRUE，自动输出返回的内容。如果失败返回FALSE
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	
		//连接超时，防止服务器卡死
		if(self::$needMs) {		
			curl_setopt($ch, CURLOPT_NOSIGNAL,true);//支持毫秒级别超时设置  
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, self::$connTimeoutMs);
			curl_setopt($ch, CURLOPT_TIMEOUT_MS, self::$timeoutMs);		
		} else {
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::$connTimeout);
			curl_setopt($ch, CURLOPT_TIMEOUT, self::$timeout);
		}
    		
	
    	self::$requestHeader && curl_setopt($ch,CURLOPT_HTTPHEADER,self::$requestHeader);
		self::$responseData = curl_exec($ch);
    	$datas = explode("\r\n\r\n", self::$responseData);
    	self::$responseHeader = array_shift($datas);
    	!empty($datas) &&  self::$responseData = implode('',$datas);

    	self::$curlInfo = curl_getinfo($ch);
    	self::$errNo =  curl_errno($ch);
    	self::$errStr =  curl_error($ch);
    	curl_close($ch);
    	@unlink(self::$cookieFile);
    	//记录日志
    	self::errorLog();
    	return self::isOk();
    	
    } 
    
    /**
     * 执行
     * @return boolean
     */
    public static function execute() {
        $frequency = min( intval(self::$frequency),self::$maxFrequency);
        
        do {
            $result = self::request();
            $frequency--;
            
        } while (!$result && $frequency>0);

        if($result) return $result;
    	
    	return false;
    }
    
    /**
     * 设置COOKIE
     * @param array $data
     */
    public static function setCookie($data = array()) {
    	$cookieStr = '';
    	if (!empty($data)) {
    		if (is_array($data)) {
    			foreach ($data as $name => $value) {
    				$cookieStr .= "$name=$value;";
    			}
    		} else {
    			$cookieStr = "$name=$value;";
    		}
    	}
    	self::$requestCookie = $cookieStr;
    }
    
    /**
     * 设置头部
     * @param array $data
     */
    public static function setHeader($data = array()) {
    	self::$requestHeader = $data;
    }
    
    /**
     * 获得cookie
     * @return array:
     */
    public static  function getCookie() {
    	$cookies = array();
    	if(preg_match_all("|Set-Cookie: ([^;]*);|", self::$responseHeader, $m)) {
    		foreach($m[1] as $c) {
    			list($k, $v) = explode('=', $c);
    			$cookies[$k] = $v;
    		}
    	}
    	return self::$responseCookie = $cookies;
    }
    
    /**
     * 获得反馈状态
     * @return array
     */
    public static function getStatus() {
    	preg_match("|^HTTP/1.1 ([0-9]{3}) (.*)|", self::$responseHeader, $m);
    	return array($m[1], $m[2]);
    }
    
    /**
     * 获得数据
     * @return array
     */
    public static function getData() {
    	if (strpos(self::$responseHeader,'chunk')) {
    		return self::$responseData;
    		/* $data = explode(chr(13), self::$responseData);
    		return $data[1]; */
    	} else {
    		return self::$responseData;
    	}
    }
    
    /**
     * 获得头部
     * @return array
     */
    public static function getHeader() {
    	return self::$responseHeader;
    }
    
    /**
     * 是否成功
     * @return boolean
     */
    public static function isOk() {
        /* $status = self::getStatus();
         if(intval($status[0]) != 200) {
         self::$errNo = $status[0];
         self::$errStr = $status[1];
         return false;
         } 
         */
        if (self::$errNo || self::$curlInfo['http_code'] != 200) {
            return false;
        }
    	return true;
    }
    
    /**
     * 错误代码
     */
    public static function errNo() {
    	return self::$errNo;
    }
    
    /**
     * 错误信息
     */
    public static function errMsg() {
    	return self::$errStr;
    }
    
    /**
     * 记录错误日志 
     * @param string $errorstr
     */
    public static function errorLog()
    {
        if (empty(self::$errNo) && self::$curlInfo['http_code'] == 200) 
            return false;
            
        //记录日志
        $errorStr='URL:'.self::$url.'|Method:'.self::$method.'|Data:'.json_encode(self::$requestData).'|Access time:'.date('Y-m-d H:i:s',time()).'|error:'.self::$errStr.'|erroro:'.self::$errNo.'|http code :'.self::$curlInfo['http_code'].' | total_time:'.self::$curlInfo['total_time'].' | connect_time:'.self::$curlInfo['connect_time'].' | namelookup_time:'.self::$curlInfo['namelookup_time'].' | starttransfer_time:'.self::$curlInfo['starttransfer_time'].' | pretransfer_time:'.self::$curlInfo['pretransfer_time'];
        $filename = LOG_PATH.'curl'.DIRECTORY_SEPARATOR.'request_error_'.date('Y-m-d',time()).'.log';

        $dir = dirname($filename);
        if(!is_dir($dir)) {
            mkdir($dir,0775,true);
        }
        
        $fp = fopen($filename,'a');
        fwrite($fp, $errorStr.chr(10));
        fclose($fp);
         
    }
   
    /**
     * 批处理
     * @param string $method
     * @param array $args
     * @throws Exception
     * @return multitype:string
     */
    public static function multiExecCurl($method, $args)
    {
    	$urls      = isset($args[0])            ? $args[0]            : "";
    	$data      = isset($args[1]['data'])    ? $args[1]['data']    : "";
    	$ajax      = isset($args[1]['ajax'])    ? $args[1]['ajax']    : "";
    	$timeout   = isset($args[1]['timeout']) ? $args[1]['timeout'] : 30;
    	$referer   = isset($args[1]['referer']) ? $args[1]['referer'] : "";
    	$proxy     = isset($args[1]['proxy'])   ? $args[1]['proxy']   : "";
    	$headers   = isset($args[1]['headers']) ? $args[1]['headers'] : "";
    
    	if (!is_array($urls) || (is_array($urls) && empty($urls))) {
    		throw new \Exception("错误信息:批处理url必须是数组并且不能为空");
    	}
    
    	//创建批处理cURL句柄
    	$queue   = curl_multi_init();
    
    	//取得cookie文件路径
    	if(!self::$cookieFile) {
            self::$cookieFile = isset($args[1]['cookie_file'])
    		? $args[1]['cookie_file'] : "";
    	}
    
    	//如果未获取到浏览器环境信息，就手动指定一个
    	$userAgent = isset($_SERVER['HTTP_USER_AGENT'])
    	? $_SERVER['HTTP_USER_AGENT']
    	: 'Mozilla/5.0 (Windows NT 6.2; WOW64; rv:23.0) '
    			.'Gecko/20100101 Firefox/23.0';
    
    	//设置CURL OPT选项
    	$options = array(
    			CURLOPT_TIMEOUT        => $timeout,  //超时
    			CURLOPT_RETURNTRANSFER => 1,         //输出数据流
    			CURLOPT_HEADER         => 0,         //禁止头文件数据流输出
    			CURLOPT_FOLLOWLOCATION => 1,         //自动跳转追踪
    			CURLOPT_AUTOREFERER    => 1,         //自动设置来路信息
    			CURLOPT_SSL_VERIFYPEER => 0,         //认证证书检查
    			CURLOPT_SSL_VERIFYHOST => 0,         //检查SSL加密算法
    			CURLOPT_HEADER         => 0,         //禁止头文件输出
    			CURLOPT_NOSIGNAL       => 1,         //忽略php所有的传递信号
    			CURLOPT_USERAGENT      => $userAgent,//浏览器环境字符串
    			CURLOPT_IPRESOLVE	   => CURL_IPRESOLVE_V4,
    	);
    
    	//检测是否存在代理请求
    	if (is_array($proxy) && !empty($proxy)) {
    
    		$options[CURLOPT_PROXY]        = $proxy['host'];
    		$options[CURLOPT_PROXYPORT]    = $proxy['port'];
    
    		$options[CURLOPT_PROXYUSERPWD] =
    		$proxy['user'] . ':' . $proxy['pass'];
    	}
    
    	//header选项
    	$headerOptions = array();
    
    	//模拟AJAX请求
    	if ($ajax) {
    		$headerOptions['X-Requested-With']  = 'XMLHttpRequest';
    	}
    
    	if (self::$cookieFile) {
    		$options[CURLOPT_COOKIEFILE] = self::$cookieFile;
    		$options[CURLOPT_COOKIEJAR]  =  self::$cookieFile;
    	}
    
    	if ($referer) {
    		$options[CURLOPT_REFERER] = $referer;
    	}
    
    	if (!empty($headerOptions)) {
    		$options[CURLOPT_HTTPHEADER] = $headerOptions;
    	}
    
    	//循环的进行初始化一个cURL会话
    	foreach ($urls as  $k => $url) {
    		$ch = curl_init();
    		curl_setopt($ch, CURLOPT_URL, $url);
    
    		if ($method == 'post') {
    			//发送一个常规的POST请求，
    			//类型为：application/x-www-form-urlencoded，就像表单提交的一样
    			$options[CURLOPT_POST]       = 1;
    
    			//使用HTTP协议中的"POST"操作来发送数据,支持键值对数组定义
    			//注意：即使不使用http_build_query也能自动编码
    			$options[CURLOPT_POSTFIELDS] = $data[$k];
    		}
    
    		curl_setopt_array($ch,$options);
    		curl_multi_add_handle($queue, $ch);
    	}
    
    	//初始化变量
    	$responses = array();
    	$active    = null;
    
    	//循环运行当前 cURL 句柄的子连接
    	do {
    		while (($code = curl_multi_exec($queue,
    				$active)) == CURLM_CALL_MULTI_PERFORM);
    
    		if ($code != CURLM_OK) {
    			break;
    		}
    
    		//循环获取当前解析的cURL的相关传输信息
    		while ($done = curl_multi_info_read($queue)) {
    
    			//获取最后一次传输的相关信息
    			$info = curl_getinfo($done['handle']);
    
    			//从最后一次传输的相关信息中找 http_code 等于200
    			if ($info['http_code'] == 200) {
    				//如果设置了CURLOPT_RETURNTRANSFER，获取的输出的文本流
    				$responses[] = curl_multi_getcontent($done['handle']);
    			}
    
    			//移除curl批处理句柄资源中的某个句柄资源
    			curl_multi_remove_handle($queue, $done['handle']);
    
    			//关闭某个批处理句柄会话
    			curl_close($done['handle']);
    		}
    
    		if ($active > 0) {
    			//等待所有cURL批处理中的活动连接
    			curl_multi_select($queue, 0.5);
    		}
    
    	} while ($active);
    
    	//关闭一组cURL句柄
    	curl_multi_close($queue);
    
    	//返回结果
    	return $responses;
    }
    
}