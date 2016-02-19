<?php
/**
 * 获取数据字典
 * @param string $key      //键值，方便查找数据
 * @param string $fileName //字典文件名 目录Common/Dict/
 * @return mixed
 */
function dict($key = '', $fileName = 'Setting') {
	static $_dictFileCache  =   array();
	$file = APP_PATH . 'Common' . DS . 'Dict' . DS . $fileName . '.php';
	if (!file_exists($file)){
		unset($_dictFileCache[$fileName]);
		return null;
	}
	if(!$key && !empty($_dictFileCache[$fileName])) return $_dictFileCache[$fileName];
	if ($key && isset($_dictFileCache[$fileName][$key])) return $_dictFileCache[$fileName][$key];
	$data = require_once $file;
	$_dictFileCache[$fileName] = $data;
	return $key ? $data[$key] : $data;
}

/**
 * 生成UUID
 * @return string 返回UUID字符串
 */
function uuid() {
	$uuid = M()->query('SELECT UUID() AS uuid;');
	return $uuid[0]['uuid'];
}

/**
 * 检测输入的验证码是否正确
 * @param string $code 为用户输入的验证码字符串
 * @param string $id   其他参数
 * @param bool   $reset 是否重置
 * @return bool
 */
function check_verify($code, $id = '', $reset = true){
	$verify = new \Think\Verify(array('reset'=>$reset));
	return $verify->check($code, $id);
}

/**
 * 对用户的密码进行加密
 * @param string $password
 * @param string $encrypt //传入加密串，在修改密码时做认证
 * @return array/string
 */
function password($password, $encrypt='') {
	$pwd = array();
	$pwd['encrypt']  = $encrypt ? $encrypt : rand(100000, 999999);
	$pwd['password'] = md5(md5(trim($password)).$pwd['encrypt']);
	return $encrypt ? $pwd['password'] : $pwd;
}

/**
 * 解析多行sql语句转换成数组
 * @param string $sql
 * @return array
 */
function sql_split($sql) {
	$sql = str_replace("\r", "\n", $sql);
	$ret = array();
	$num = 0;
	$queriesarray = explode(";\n", trim($sql));
	unset($sql);
	foreach($queriesarray as $query) {
		$ret[$num] = '';
		$queries = explode("\n", trim($query));
		$queries = array_filter($queries);
		foreach($queries as $query) {
			$str1 = substr($query, 0, 1);
			if($str1 != '#' && $str1 != '-') $ret[$num] .= $query;
		}
		$num++;
	}
	return($ret);
}

/**
 * 文件上传
 * @param array $files
 * @param array $config
 * @return array
 */
function upload($files, $config = array()){
	$config = array_merge(C('FILE_UPLOAD_CONFIG'), $config);

	$upload = new Think\Upload($config);
	$res    = $upload->upload($files);
	if($res){
		foreach($res as $arr){
			$filename = UPLOAD_PATH . $arr['savepath'] . $arr['savename'];
			if(C('IMAGE_WATER_CONFIG.status') && strpos($arr['type'], 'image') !== false){
				image_water($filename);
			}
		}
		return array('status'=>1, 'info'=>'上传成功', 'result'=>$res);
	}else{
		return array('status'=>0, 'info'=>$upload->getError(), 'result'=>null);
	}
}

/**
 * PHP5.4 新增函数，此处为了兼容5.4以下版本
 */
if (!function_exists('getimagesizefromstring')) {
	/**
	 * 通过内容获取图片信息
	 * @param $string
	 * @return array
	 */
	function getimagesizefromstring($string){
		$uri = 'data://application/octet-stream;base64,'  . base64_encode($string);
		return getimagesize($uri);
	}
}

/**
 * 图片水印
 * @param $image
 * @param bool $isFile
 * @param string $newImage
 * @param array $config
 * @return bool
 */
function image_water($image, $isFile = true, $newImage = '', $config = array()){
	$config = array_merge(C('IMAGE_WATER_CONFIG'), $config);

	if($isFile){
		if(file_exists($image)){
			if(empty($newImage)) $newImage = $image;
			$image = file_get_contents($image);
		}else{
			if(!file_exist($image)) return false;
			if(empty($newImage)) $newImage = $image;
			$image = file_read($image);
		}
	}
	if(empty($newImage)) return false;

	$imagine = new Common\Plugin\Imagine();
	$imagine->load($image);
	$imagine->watermark($config);

	return file_write($newImage, $imagine->get());
}

/**
 * 图片缩略图
 * @param $image
 * @param bool $isFile
 * @param string $newImage
 * @param array $config
 * @return bool
 */
function image_thumb($image, $isFile = true, $newImage = '', $config = array()){
	if($isFile){
		if(file_exists($image)){
			if(empty($newImage)) $newImage = $image;
			$image = file_get_contents($image);
		}else{
			if(!file_exist($image)) return false;
			if(empty($newImage)) $newImage = $image;
			$image = file_read($image);
		}
	}
	if(empty($newImage)) return false;

	$width  = intval($config['width']);
	$height = intval($config['height']);
	$type   = $config['type'] ?: 'force';
	$color  = $config['color'] ?: '#ffffff';  //仅type为scale_color时生效
	$alpha  = isset($config['alpha']) ? $config['alpha'] : 100; //gif图片暂不支持

	$imagine = new Common\Plugin\Imagine();
	$imagine->load($image);
	$imagine->thumb($width, $height, $type, $color, $alpha);

	return file_write($newImage, $imagine->get());
}

/**
 * 图片裁剪
 * @param array $param
 * @param string $image
 * @param bool $isFile
 * @param string $newImage
 * @return bool
 */
function image_crop($param, $image, $isFile = true, $newImage = ''){
	if($isFile){
		if(file_exists($image)){
			if(empty($newImage)) $newImage = $image;
			$image = file_get_contents($image);
		}else{
			if(!file_exist($image)) return false;
			if(empty($newImage)) $newImage = $image;
			$image = file_read($image);
		}
	}
	if(empty($newImage)) return false;

	$imagine = new Common\Plugin\Imagine();
	$imagine->load($image);
	$imagine->crop($param['x'], $param['y'], $param['w'], $param['h'], $param['width'], $param['height']);

	return file_write($newImage, $imagine->get());
}

/**
 * 取得文件扩展
 * @param string $filename 文件名
 * @return string
 */
function file_ext($filename) {
	return pathinfo($filename, PATHINFO_EXTENSION);
}

/**
 * 文件是否存在
 * @param string $filename  文件名
 * @return boolean  
 */
function file_exist($filename){
	$file = new Common\Plugin\File();
	return $file->exist($filename);
}

/**
 * 文件内容读取
 * @param string $filename  文件名
 * @return bool
 */
function file_read($filename){
	$file = new Common\Plugin\File();
	return $file->read($filename);
}

/**
 * 文件写入
 * @param string $filename  文件名
 * @param string $content  文件内容
 * @return bool
 */
function file_write($filename, $content){
	$file = new Common\Plugin\File();
	return $file->write($filename, $content);
}

/**
 * 文件追加
 * @param string $filename  文件名
 * @param string $content   文件内容
 * @return bool
 */
function file_append($filename, $content){
	$file = new Common\Plugin\File();
	return $file->append($filename, $content);
}

/**
 * 文件删除
 * @param string $filename 文件名
 * @return bool
 */
function file_delete($filename){
	$file = new Common\Plugin\File();
	return $file->unlink($filename);
}

/**
 * 文件夹列表
 * @param $path
 * @return mixed
 */
function file_dir($path){
	$file = new Common\Plugin\File();
	return $file->dir($path);
}

/**
 * 验证远程链接地址是否正确
 * @param string $url
 * @param int $timeout
 * @return bool
 */
function file_exist_remote($url, $timeout = 1){
	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_NOBODY, true);
	curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
	$result = curl_exec($curl);
	$found = false;
	if ($result !== false) {
		$statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		if ($statusCode == 200) $found = true;
	}
	curl_close($curl);
	return $found;
}

/**
 * 远程文件内容读取
 * @param string $url
 * @param int $timeout
 * @return string
 */
function file_read_remote($url, $timeout = 3){
	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_HEADER,0);
	curl_setopt($curl, CURLOPT_NOBODY, false);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
	curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
	$result = curl_exec($curl);
	curl_close($curl);
	return $result;
}

/**
 * 文件名加后缀
 * @param string $string
 * @param string $subfix
 * @return string
 */
function file_subfix($string, $subfix = ''){
	return preg_replace("/(\.\w+)$/", "{$subfix}\\1", $string);
}

/**
 * 上传文件完整地址
 * @param string $path
 * @return string
 */
function file_full_path($path = ''){
	if(empty($path)) return $path;
	$config = C('TMPL_PARSE_STRING');
	return $config[UPLOAD_PATH] . str_replace(UPLOAD_PATH, '', $path);
}

/**
 * xml转数组
 * @param string $xml
 * @param bool $isFile
 * @return null|array
 */
function xml2array($xml, $isFile = false){
	if($isFile && file_exist($xml)) $xml = file_read($xml);
	$xml = @simplexml_load_string($xml);

	if(is_object($xml)){
		$xml = json_encode($xml);
		$xml = @json_decode($xml, true);
	}
	if(!is_array($xml)) return null;

	return $xml;
}

/**
 * 格式化字节大小
 * @param  number $size      字节数
 * @param  string $delimiter 数字和单位分隔符
 * @return string            格式化后的带单位的大小
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function format_bytes($size, $delimiter = '') {
	$units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
	for ($i = 0; $size >= 1024 && $i < 5; $i++) $size /= 1024;
	return round($size, 2) . $delimiter . $units[$i];
}

/**
 * 发送邮件
 * @param string $to      收件人
 * @param string $subject 主题
 * @param string $body    内容
 * @param array $config
 * @return bool
 */
function send_email($to, $subject, $body, $config = array()){
	$email = new \Common\Plugin\Email($config);
	$email->send($to, $subject, $body);
	return $email->result;
}

/**
 * 生成签名
 * @param array $param
 * @return string
 */
function sign($param = array()){
	return md5(base64_encode(hash_hmac('sha1', http_build_query($param), C('API_SIGN'), true)));
}

/**
 * 处理插件钩子
 * @param string $hook   钩子名称
 * @param mixed $params 传入参数
 * @return void
 */
function hook($hook, $params=array()){
	\Think\Hook::listen($hook,$params);
}

/**
 * 获取插件类的类名
 * @param strng $name 插件名
 * @return string
 */
function get_addon_class($name){
	$class = "Addons\\{$name}\\{$name}Addon";
	return $class;
}

/**
 * 生成用户头像
 * @param $head 头像路径
 * @return string
 */
function member_head($head){
	$path = C('TMPL_PARSE_STRING.__STATIC__') . '/img/head/';
	$img  = 'head.png';
	if($head){
		if(preg_match("/^https?:\/\//i", $head)){
			$path = '';
			$img  = $head;
		}else{
			$config = C('TMPL_PARSE_STRING');
			$path   = $config[UPLOAD_PATH];
			$img    = $head;
		}
	}
	return $path . $img;
}

/**
 * 生成用户名
 */
function member_username(){
	$max = M('member')->where('username > 99999')->order('username desc')->getField('username');
	if(!$max) $max = 99999;
	return ++$max;
}