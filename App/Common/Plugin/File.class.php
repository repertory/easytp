<?php
namespace Common\Plugin;

class File{
	private $param = array();

	public function __construct(){
		$type = ucfirst(C('FILE_UPLOAD_TYPE'));
		$class = __NAMESPACE__ . '\\' . $type . 'File';

		if(!class_exists($class)) $class = __NAMESPACE__ . '\\LocalFile';

		$this->file = new $class();
	}

	public function __set($key, $value){
		$this->param[$key] = $value;
	}

	public function __get($key){
		return $this->param[$key];
	}

	public function __destruct(){
		if($this->param !== null) $this->param = array();
	}

	public function exist($filename){
		return $this->file->exist($filename);
	}

	public function read($filename){
		return $this->file->read($filename);
	}

	public function write($filename, $content){
		return $this->file->write($filename, $content);
	}

	public function append($filename, $content){
		return $this->file->append($filename, $content);
	}

	public function unlink($filename){
		return $this->file->unlink($filename);
	}

	public function dir($path){
		return $this->file->dir($path);
	}
}

class LocalFile{
	public function exist($filename){
		return file_exists($filename);
	}

	public function read($filename){
		return file_get_contents($filename);
	}

	public function write($filename, $content){
		$dir = dirname($filename);
		if(!is_dir($dir)){
			mkdir($dir, 0777, true);
		}

		return file_put_contents($filename, $content);
	}

	public function append($filename, $content){
		$dir = dirname($filename);
		if(!is_dir($dir)){
			mkdir($dir, 0777, true);
		}

		return file_put_contents($filename, $content, FILE_APPEND);
	}

	public function unlink($filename){
		return is_file($filename) ? unlink($filename) : false;
	}

	public function dir($path){
		if(!is_dir($path)) $path = dirname(path);

		$path = realpath($path);
		$path = str_replace(array('/', '\\'), DS, $path);
		$list = glob($path . DS . '*');
		$res  = array();
		foreach ($list as $key => $filename) {
			if(is_dir($filename)){
				array_push($res, array(
					'name'  => basename($filename),
					'type'  => 'dir',
					'size'  => format_bytes(filesize($filename), ' '),
					'mtime' => date('Y-m-d H:i:s', filemtime($filename)),
					'path'  => str_replace(array($path, '\\'), array('', '/'), $filename),
					'mime'  => mime_content_type($filename),
					'ext'   => file_ext($filename),
				));
			}else{
				array_push($res, array(
					'name'  => basename($filename),
					'type'  => 'file',
					'size'  => format_bytes(filesize($filename), ' '),
					'mtime' => date('Y-m-d H:i:s', filemtime($filename)),
					'path'  => str_replace(array($path, '\\'), array('', '/'), $filename),
					'mime'  => mime_content_type($filename),
					'ext'   => file_ext($filename),
				));
			}
		}
		return $res;
	}
}

class SaeFile{
	private $object;

	public function __construct(){
		$this->object = new \SaeStorage();
	}

	public function exist($filename){
		$arr      = explode('/', ltrim($filename, './'));
		$domain   = array_shift($arr);
		$filePath = implode('/', $arr);

		return $this->object->fileExists($domain, $filePath);
	}

	public function read($filename){
		$arr      = explode('/', ltrim($filename, './'));
		$domain   = array_shift($arr);
		$filePath = implode('/', $arr);

		return $this->object->read($domain, $filePath);
	}

	public function write($filename, $content){
		$arr       = explode('/',ltrim($filename,'./'));
		$domain    = array_shift($arr);
		$save_path = implode('/',$arr);

		return $this->object->write($domain, $save_path, $content);
	}

	public function append($filename, $content){
		$read = $this->read($filename);

		if($read) $content = $read . $content;

		return $this->write($filename, $content);
	}

	public function unlink($filename){
		$arr       = explode('/', trim($filename, './'));
		$domain    = array_shift($arr);
		$filePath  = implode('/', $arr);

		return $this->object->delete($domain, $filePath);
	}

	public function dir($path){
		$arr      = explode('/', trim($path, './'));
		$domain   = array_shift($arr);
		$filePath = implode('/', $arr);

		$list     = $this->object->getListByPath($domain, $filePath);
		$res      = array();

		while(isset($list['dirNum']) && $list['dirNum']){
			$list['dirNum']--;

			array_push($res, array(
				'name'  => $list['dirs'][$list['dirNum']]['Name'],
				'type'  => 'dir',
				'size'  => '',
				'mtime' => '',
				'path'  => str_replace($filePath, '', $list['dirs'][$list['dirNum']]['fullName']),
				'mime'  => '',
				'ext'   => '',
			));
		}

		while(isset($list['fileNum']) && $list['fileNum']){
			$list['fileNum']--;

			array_push($res, array(
				'name'  => $list['files'][$list['fileNum']]['Name'],
				'type'  => 'file',
				'size'  => format_bytes($list['files'][$list['fileNum']]['length'], ' '),
				'mtime' => date('Y-m-d H:i:s', $list['files'][$list['fileNum']]['uploadTime']),
				'path'  => str_replace($filePath, '', $list['files'][$list['fileNum']]['fullName']),
				'mime'  => '',
				'ext'   => file_ext($list['files'][$list['fileNum']]['Name']),
			));
		}
		return $res;
	}
}

class OssFile{
	private $hostname = 'oss.aliyuncs.com';
	private $timeout  = 5184000;

	public function __construct(){
		$this->access_id  = C('UPLOAD_TYPE_CONFIG.access_id');
		$this->access_key = C('UPLOAD_TYPE_CONFIG.access_key');
		$this->bucket     = C('UPLOAD_TYPE_CONFIG.bucket');
	}

	public function exist($filename, $options = array()){
		$arr      = explode('/', ltrim($filename, './'));
		array_shift($arr);
		$filename = implode('/', $arr);

		$options['method'] = 'HEAD';
		$options['object'] = $filename;
		$info = $this->auth($options);
		return $info === false ? false : true;
	}

	public function read($filename, $options = array()){
		$arr      = explode('/', ltrim($filename, './'));
		array_shift($arr);
		$filename = implode('/', $arr);

		$options['method'] = 'GET';
		$options['object'] = $filename;

		return $this->auth($options);
	}

	public function write($filename, $content){
		$arr      = explode('/', ltrim($filename, './'));
		array_shift($arr);
		$filename = implode('/', $arr);

		$options = array(
			'content' => $content,
			'length'  => strlen($content),
		);

		$options['method']         = 'PUT';
		$options['object']         = $filename;
		$options['Content-Length'] = $options['content'];

		$info = $this->auth($options);
		return $info === false ? false : true;
	}

	public function append($filename, $content){
		$read = $this->read($filename);

		if($read) $content = $read . $content;

		return $this->write($filename, $content);
	}

	public function unlink($filename, $options = array()){
		$arr      = explode('/', ltrim($filename, './'));
		array_shift($arr);
		$filename = implode('/', $arr);

		$options['method'] = 'DELETE';
		$options['object'] = $filename;

		$info = $this->auth($options);
		return $info === false ? false : true;
	}

	public function dir($path, $options = array()){
		$arr = explode('/', trim($path, './'));
		array_shift($arr);
		$path = implode('/', $arr);

		$options['method'] = 'GET';
		$options['object'] = '/';
		$options['prefix'] = $path . '/';
		$options['headers'] = array(
			'delimiter' => isset($options['delimiter'])?$options['delimiter']:'/',
			'prefix' => isset($options['prefix'])?$options['prefix']:'',
			'max-keys' => isset($options['max-keys'])?$options['max-keys']:200,
			'marker' => isset($options['marker'])?$options['marker']:'',
		);
		$info = $this->auth($options);

		$res = array();
		if($info !== false){
			$list = xml2array($info);
			if(!isset($list['CommonPrefixes'][0]['Prefix'])) $list['CommonPrefixes'] = array($list['CommonPrefixes']);
			foreach($list['CommonPrefixes'] as $info){
				array_push($res, array(
					'name'  => basename($info['Prefix']),
					'type'  => 'dir',
					'size'  => '',
					'mtime' => '',
					'path'  => str_replace($path, '', $info['Prefix']),
					'mime'  => '',
					'ext'   => '',
				));
			}

			if(!isset($list['Contents'][0]['Key'])) $list['Contents'] = array($list['Contents']);
			foreach($list['Contents'] as $info){
				array_push($res, array(
					'name'  => basename($info['Key']),
					'type'  => 'file',
					'size'  => format_bytes($info['Size'], ' '),
					'mtime' => $info['LastModified'],
					'path'  => str_replace($path, '', $info['Key']),
					'mime'  => '',
					'ext'   => file_ext($info['Key']),
				));
			}
		}
		return $res;
	}

	/**
	 * auth接口
	 * @param array $options
	 * @return false|string
	 */
	private function auth($options){
		//请求参数
		$signable_resource = '';
		$string_to_sign    = '';

		$request_url =  "https://{$this->hostname}/{$this->bucket}";

		if (isset($options['object']) && '/' !== $options['object']){
			$signable_resource .= '/'.str_replace(array('%2F', '%25'), array('/', '%'), rawurlencode($options['object']));
			$request_url       .= $signable_resource;
		}

		$headers = array (
			'Content-Md5'  => '',
			'Content-Type' => isset($options['Content-Type']) ? $options['Content-Type'] : 'application/x-www-form-urlencoded',
			'Date'         => isset($options['Date']) ? $options['Date'] : gmdate('D, d M Y H:i:s \G\M\T'),
			'Host'         => $this->hostname,
		);

		//合并 HTTP headers
		if (isset($options['headers'])) {
			$headers = array_merge($headers, $options['headers']);
		}

		if (isset($options['Content-Md5'])){
			$headers['Content-Md5'] = $options['Content-Md5'];
		}

		$method = 'GET';
		if(isset($options['method'])){
			$method = $options['method'];
			$string_to_sign .= $options['method'] . "\n";
		}

		$request_body = '';
		if (isset($options['content'])) {
			$request_body = $options['content'];

			if ($headers['Content-Type'] === 'application/x-www-form-urlencoded'){
				$headers['Content-Type'] = 'application/octet-stream';
			}

			$headers['Content-Length'] = strlen($options['content']);
			$headers['Content-Md5']    = base64_encode(md5($options['content'], true));
		}

		uksort($headers, 'strnatcasecmp');

		$request_headers = array();
		foreach($headers as $header_key => $header_value){
			$header_value = str_replace(array ("\r", "\n"), '', $header_value);
			if ($header_value !== '') {
				$request_headers[$header_key] = $header_value;
			}

			if (in_array_case($header_key, array('content-md5', 'content-type', 'date'))){
				$string_to_sign .= $header_value . "\n";
			}elseif (substr(strtolower($header_key), 0, 6) === 'x-oss-'){
				$string_to_sign .= strtolower($header_key) . ':' . $header_value . "\n";
			}
		}

		$string_to_sign .= '/' . $this->bucket . rawurldecode($signable_resource);

		$signature = base64_encode(hash_hmac('sha1', $string_to_sign, $this->access_key, true));
		$request_headers['Authorization'] = 'OSS ' . $this->access_id . ':' . $signature;

		$curl_handle = curl_init();

		curl_setopt($curl_handle, CURLOPT_URL, $request_url);
		curl_setopt($curl_handle, CURLOPT_FILETIME, true);
		curl_setopt($curl_handle, CURLOPT_FRESH_CONNECT, false);
		curl_setopt($curl_handle, CURLOPT_CLOSEPOLICY, CURLCLOSEPOLICY_LEAST_RECENTLY_USED);
		curl_setopt($curl_handle, CURLOPT_MAXREDIRS, 5);
		curl_setopt($curl_handle, CURLOPT_HEADER, false);
		curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl_handle, CURLOPT_TIMEOUT, $this->timeout);
		curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 120);
		curl_setopt($curl_handle, CURLOPT_NOSIGNAL, true);
		curl_setopt($curl_handle, CURLOPT_REFERER, $request_url);

		curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, false); // 对认证证书来源的检查
		curl_setopt($curl_handle, CURLOPT_SSL_VERIFYHOST, false); // 从证书中检查SSL加密算法是否存在

		if (!ini_get('safe_mode') && !ini_get('open_basedir')) curl_setopt($curl_handle, CURLOPT_FOLLOWLOCATION, true);// 使用自动跳转
		if (extension_loaded('zlib')) curl_setopt($curl_handle, CURLOPT_ENCODING, '');

		$temp_headers = array();
		foreach ($request_headers as $k => $v) $temp_headers[] = "{$k}: {$v}";
		curl_setopt($curl_handle, CURLOPT_HTTPHEADER, $temp_headers);

		switch ($method){
			case 'PUT':
				curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, 'PUT');
				curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $request_body);
				break;

			case 'POST':
				curl_setopt($curl_handle, CURLOPT_POST, true);
				curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $request_body);
				break;

			case 'HEAD':
				curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, 'HEAD');
				curl_setopt($curl_handle, CURLOPT_NOBODY, 1);
				break;

			default:
				curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, $method);
				curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $request_body);
		}
		$data = curl_exec($curl_handle);
		$code = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
		curl_close($curl_handle);

		if ($code == 200){
			return $data;
		} else {
			\Think\Log::record(var_export(array('code'=>$code, 'body'=>$data), true), 'OSS_ERROR', true);
			return false;
		}
	}
}