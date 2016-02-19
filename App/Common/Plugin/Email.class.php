<?php
namespace Common\Plugin;

class Email{
	public $config = array(
		'host'      => null,
		'port'      => 25,
		'user'      => null,
		'pass'      => null,
		'from'      => null,
		'charset'   => 'utf-8',
		'debug'     => false,
		'isHtml'    => false,
		'param'     => array('socket_create', 'fsockopen'),
		'method'    => null,
		'attach'    => null,              //附件
		'read'      => 'file_read',        //附件读取方法
		'boundary'  => '079ad17405c889e0', //附件分割符
	);

	public function __set($key, $value){
		$this->config[$key] = $value;
	}

	public function __get($key){
		return $this->config[$key];
	}
	
	public function __construct($config = array()){
		if(is_array($config)) $this->config = array_merge($this->config, $config);

		if(is_null($this->host)) $this->host = C('EMAIL_CONFIG.smtp');
		if(is_null($this->port)) $this->port = C('EMAIL_CONFIG.port');
		if(is_null($this->user)) $this->user = C('EMAIL_CONFIG.user');
		if(is_null($this->pass)) $this->pass = C('EMAIL_CONFIG.password');
		if(is_null($this->from)) $this->from = C('EMAIL_CONFIG.from');

		foreach($this->param as $index=>$method){
			if(function_exists($method)){
				$this->method = $index;
				$this->record("检测函数 {$method} 通过");
				break;
			}
		}
		if(is_null($this->method)) $this->record('当前环境不支持发送邮件', true);

		$this->host = gethostbyname($this->host);
		$this->user = base64_encode($this->user);
		$this->pass = base64_encode($this->pass);

		$from = explode('|', $this->from, 2);
		$this->from     = $from[0];   //发件人邮箱
		$this->fromName = $from[1];   //发件人姓名
	}

	//发送方法
	public function send($to='', $subject='', $body=''){
		if(is_null($this->method)){
			$this->result = false;
			return false;
		}

		if(!$to || !$subject || !$body){
			$this->record('收信人信息不全', true);
			$this->result = false;
			return false;
		}

		$to = explode('|', $to, 2);
		$this->to      = $to[0];   //收件人邮箱
		$this->toName  = $to[1];   //收件人姓名
		$this->subject = $subject; //邮件主题
		$this->body    = $body;    //邮件内容

		if(strtolower($this->charset) != 'utf-8'){
			$this->subject  = iconv('utf-8', $this->charset, $this->subject);
			$this->body     = iconv('utf-8', $this->charset, $this->body);
			$this->toName   = iconv('utf-8', $this->charset, $this->toName);
			$this->fromName = iconv('utf-8', $this->charset, $this->fromName);
		}

		$method = $this->param[$this->method];
		if(!method_exists($this, $method)){
			$this->record("调用方法 {$method} 不存在", true);
			$this->result = false;
			return false;
		}

		$this->$method();
	}

	private function socket_create(){
		$this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		if($this->socket){
			$this->record('创建SOCKET:' . socket_strerror(socket_last_error()));
		}else{
			$this->record('初始化失败，请检查您的网络连接和参数', true);
			$this->result = false;
			return false;
		}
		$conn = socket_connect($this->socket, $this->host, $this->port);
		if($conn){
			$this->record('创建SOCKET连接:' . socket_strerror(socket_last_error()));
		}else{
			$this->record('初始化失败，请检查您的网络连接和参数', true);
			$this->result = false;
			return false;
		}
		$this->record("服务器应答：<font color=#cc0000>".socket_read($this->socket, 1024)."</font>");

		$this->handle();
	}

	private function socket_create_call($params){
		socket_write($this->socket, $params[0], strlen($params[0]));
		$this->record("客户机命令：{$params[0]}");
		$msg = socket_read($this->socket, 1024);
		$this->record("服务器应答：<font color=#cc0000>{$msg}</font>");

		if(isset($params[1]) && strpos($msg, $params[1]) === false){
			$this->record($params[2], true);
			$this->result = false;
		}
	}

	// fsockopen函数发送
	private function fsockopen(){
		$this->socket = fsockopen($this->host, $this->port, $errno, $errstr, 60);
		if($this->socket){
			$this->record("创建SOCKET连接:".$this->host.':'.$this->port);
		}else{
			$this->record('初始化失败，请检查您的网络连接和参数'.$errstr, true);
			$this->result = false;
			return false;
		}
		stream_set_blocking($this->socket, true);
		$this->handle();
	}

	private function fsockopen_call($params){
		fputs($this->socket, $params[0]);
		$this->record("客户机命令：{$params[0]}");
		$msg = fgets($this->socket, 512);
		$this->record("服务器应答：<font color=#cc0000>{$msg}</font>");

		if(isset($params[1]) && strpos($msg, $params[1]) === false){
			$this->record($params[2], true);
			$this->result = false;
		}
	}

	private function handle(){
		$all  = array();
		$from = $this->fromName ? "{$this->fromName}<{$this->from}>" : $this->from;
		$to   = $this->toName ? "{$this->toName}<{$this->to}>" : $this->to;
		array_push($all, "From:{$from}\r\n");
		array_push($all, "To:{$to}\r\n");
		array_push($all, "Subject:=?{$this->charset}?B?" . base64_encode($this->subject) . "?=\r\n");

		if($this->attach){
			array_push($all, "MIME-Version: 1.0\r\n");
			array_push($all, "Content-type: multipart/mixed; boundary=\"{$this->boundary}\";\r\n");
			array_push($all, "--{$this->boundary}\r\n");
		}

		/* 文字 */
		$this->isHtml ? array_push($all, "Content-Type: text/html;\r\n") : array_push($all, "Content-Type: text/plain;\r\n");  //邮件类型 html或文本
		array_push($all, "charset: {$this->charset}\r\n");
		//告诉浏览器信件内容进过了base64编码，最后必须要发一组\r\n产生一个空行,表示头部信息发送完毕
		array_push($all, "Content-Transfer-Encoding: base64\r\n\r\n");
		array_push($all, base64_encode($this->body));

		/* 附件 */
		if($this->attach){
			$read = $this->read;  //读取文件内容方法
			foreach($this->attach as $attach){
				if(is_string($attach)) $attach = array('path'=>$attach);
				if(!isset($attach['name']))     $attach['name']     = basename($attach['path']);
				if(!isset($attach['mimetype'])) $attach['mimetype'] = 'application/octet-stream';

				//编码转换
				if(strtolower($this->charset) != 'utf-8'){
					$attach['name'] = iconv('utf-8', $this->charset, $attach['name']);
				}

				array_push($all, "\r\n\r\n");
				array_push($all, "--{$this->boundary}\r\n");
				array_push($all, "Content-type: {$attach['mimetype']}; name=\"{$attach['name']}\";\r\n");
				array_push($all, "Content-disposition: inline; name=\"{$attach['name']}\";\r\n");
				array_push($all, "Content-Transfer-Encoding: base64\r\n\r\n");

				array_push($all, chunk_split(base64_encode($read($attach['path']))));
			}

			//结束符
			array_push($all, "\r\n\r\n--{$this->boundary}--");
		}

		$all = implode('', $all);

		$method = $this->param[$this->method];
		$call   = $method . '_call';

		if(!method_exists($this, $call)){
			$this->record("调用方法 {$call} 不存在", true);
			$this->result = false;
			return false;
		}

		$items = array(
			array("EHLO wangdong\r\n"),
			array("AUTH LOGIN\r\n"),
			array("{$this->user}\r\n"),
			array("{$this->pass}\r\n", '235', 'smtp 认证失败'),
			array("MAIL FROM:<{$this->from}>\r\n", '250', '邮件发送失败'),
			array("RCPT TO:<{$this->to}>\r\n", '250', '邮件发送失败'),
			array("DATA\r\n", '354', '邮件发送失败'),
			array("{$all}\r\n.\r\n", '250', '邮件发送失败'),
			array("QUIT\r\n"),
		);

		//以下是和服务器会话
		foreach($items as $index=>$params){
			$this->$call($params);
			if($this->result === false) return false;

			if($index == 0){
				// fsockopen需要单独处理
				while ($method == 'fsockopen') {
					$msg = fgets($this->socket, 512);
					$this->record("服务器应答：<font color=#cc0000>{$msg}</font>");
					if ((substr($msg, 3, 1) != '-') || empty($msg)) break;
				}
			}
		}

		if($this->result !== false) $this->result = true;
	}

	//调试记录
	private function record($msg, $save = false){
		if($save) $this->error = $msg;
		if($this->debug) printf("<p>%s</p>\n", $msg);
	}

	//关闭socket
	public function __destruct(){
		$method = $this->param[$this->method];
		switch ($method){
			case 'socket_create':
				socket_close($this->socket);
				break;
			case 'fsockopen':
				fclose($this->socket);
				break;
		}
		$this->config = null;
	}
}