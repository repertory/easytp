<?php
namespace Install\Controller;
use Think\Controller;
use Think\Db;
use Think\Storage;

class IndexController extends Controller {
	public function index($debug = ''){
		if($debug) file_delete(UPLOAD_PATH . 'install.lock');

		$status = array('status'=>1, 'url'=>U('Index/step1'));
		if(file_exist(UPLOAD_PATH . 'install.lock')){
			$status = array('status'=>0, 'url'=>U('/'), 'msg'=>'已经成功安装了，请不要重复安装!');
		}
		$this->assign('status', $status);
		$this->display();
	}

	public function step1(){
		$items = array(
			//运行环境
			array(
				'os'     => array('操作系统', 1, null, PHP_OS),
				'php'    => array('PHP版本',  0, '5.3', PHP_VERSION),
				'upload' => array('附件上传', 1, null, (function_exists('ini_get') ? ini_get('upload_max_filesize') : '未知')),
				'disk'   => array('磁盘空间', 1, null, (function_exists('disk_free_space') ? format_bytes(disk_free_space(realpath(SITE_DIR))) : '未知')),
			),
			//扩展支持
			array(
				'pdo'        => array('pdo', 0, null, '不支持', 'class_exists'),
				'pdo_mysql' => array('pdo_mysql', 0, null, '不支持', 'extension_loaded'),
				'json'       => array('json', 0, null, '不支持', 'extension_loaded'),
				'curl_init' => array('curl', 0, null, '不支持', 'function_exists'),
				'gd'         => array('gd', 0, null, '不支持', 'extension_loaded'),
				'Imagick'   => array('imagick', 0, null, '建议开启(可选)', 'class_exists'),
				'Gmagick'   => array('gmagick', 0, null, '建议开启(可选)', 'class_exists'),
			),
			//权限检测
			array(
				array(str_replace(SITE_DIR, '.', CONF_PATH . 'config.php'), 0, null, '不可写', CONF_PATH . 'config.php'),
				array(str_replace(SITE_DIR, '.', RUNTIME_PATH), 0, null, '不可写', RUNTIME_PATH),
				array(UPLOAD_PATH, 0, null, '不可写', UPLOAD_PATH),
			),
		);
		if(APP_MODE == 'sae') unset($items[2]);

		$status = true;

		foreach($items as $k=>&$item){
			foreach($item as $k2=>&$info){
				if($info[1]) continue;

				switch($k){
					case 0: //运行环境
						if($k2 == 'php'){
							if($info[3] >= $info[2]){
								$info[1] = 1;
							}else{
								$status = false;
							}
						}
						break;

					case 1: //扩展支持
						if($info[4]($k2)){
							$info[1] = 1;
							$info[3] = '支持';
						}else{
							if(in_array($k2, array('Imagick', 'Gmagick'))) break;
							$status = false;
						}
						break;

					case 2: //权限检测
						if(is_writable($info[4])){
							$info[1] = 1;
							$info[3] = '可写';
						}else{
							$status = false;
						}
						break;
				}
			}
		}
		if($status) session('install_step', 2);

		$this->assign('item', array($status, $items));
		$this->display();
	}

	public function step2(){
		if(IS_POST){
			$field    = array('DB_TYPE', 'DB_HOST', 'DB_USER', 'DB_PWD', 'DB_PORT', 'DB_PREFIX');
			$database = array();
			foreach($field as $key){
				$database[$key] = I("post.{$key}");
			}
			$db  = Db::getInstance($database);

			$dbname = I('post.DB_NAME');
			$sql = "CREATE DATABASE IF NOT EXISTS `{$dbname}` DEFAULT CHARACTER SET utf8";
			$db->execute($sql) || $this->error($db->getError());

			session('install_config', $_POST);
			session('install_step', 3);
			$this->success('操作成功', U('Index/step3'));
		}else{
			if(APP_MODE == 'sae'){
				C('DB_HOST', SAE_MYSQL_HOST_M);
				C('DB_PORT', SAE_MYSQL_PORT);
			}
			$this->display();
		}
	}

	public function step3(){
		$data     = session('install_config');
		if(!$data) $this->error('非法访问');

		$field    = array('DB_TYPE', 'DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PWD', 'DB_PORT', 'DB_PREFIX');
		$database = array();
		foreach($field as $key){
			$database[$key] = $data[$key];
		}
		$db = Db::getInstance($database);

		//sql字段替换
		$sql = file_get_contents(MODULE_PATH . 'Data/sql.sql');
		$sql = str_replace('[[DB_PREFIX]]', $data['DB_PREFIX'], $sql);

		//将sql文件解析成单条语句
		$ret = sql_split($sql);

		//创建管理员账号
		$passwordInfo = password($data['password']);
		$password     = $passwordInfo['password'];
		$encrypt      = $passwordInfo['encrypt'];
		$email        = trim($data['email']);
		array_push($ret, "update {$data['DB_PREFIX']}admin set `username`='{$data['username']}',`password`='{$password}',`roleid`='1',`encrypt`='{$encrypt}',`email`='{$email}' where `userid`='1'");

		$tip = array();  //执行情况统计

		//安装进度显示
		array_push($tip, array('开始安装数据库', ''));
		foreach($ret as $value){
			$value = trim($value);
			if(empty($value)) continue;
			if(substr($value, 0, 12) == 'CREATE TABLE') {
				$name = preg_replace("/^CREATE TABLE `(\w+)`.*/is", "\\1", $value);
				$msg  = "创建数据表{$name}";
				if(false !== $db->execute($value)){
					array_push($tip, array($msg, '成功'));
				} else {
					array_push($tip, array($msg, '失败'));
				}
			}elseif(substr($value, 0, 11) == 'INSERT INTO'){
				$name = preg_replace("/^INSERT INTO `(\w+)`.*/is", "\\1", $value);
				$msg  = "写入数据到{$name}";
				if(false !== $db->execute($value)){
					array_push($tip, array($msg, '成功'));
				} else {
					array_push($tip, array($msg, '失败'));
				}
			}else {
				$db->execute($value);
			}
		}

		//同步配置文件
		if(APP_MODE != 'sae'){
			$configFile = CONF_PATH.'config.php';
			$data =  file_get_contents($configFile);

			$data = preg_replace("/('DB_TYPE'\s*=>\s*)'(.*)',/Us", "\\1'{$database['DB_TYPE']}',", $data);
			$data = preg_replace("/('DB_HOST'\s*=>\s*)'(.*)',/Us", "\\1'{$database['DB_HOST']}',", $data);
			$data = preg_replace("/('DB_NAME'\s*=>\s*)'(.*)',/Us", "\\1'{$database['DB_NAME']}',", $data);
			$data = preg_replace("/('DB_USER'\s*=>\s*)'(.*)',/Us", "\\1'{$database['DB_USER']}',", $data);
			$data = preg_replace("/('DB_PWD'\s*=>\s*)'(.*)',/Us", "\\1'{$database['DB_PWD']}',", $data);
			$data = preg_replace("/('DB_PORT'\s*=>\s*)'(.*)',/Us", "\\1'{$database['DB_PORT']}',", $data);
			$data = preg_replace("/('DB_PREFIX'\s*=>\s*)'(.*)',/Us", "\\1'{$database['DB_PREFIX']}',", $data);
			$data = preg_replace("/('report'\s*=>\s*)'(.*)',/Us", "\\1'{$email}',", $data);
			file_put_contents($configFile, $data);
			array_push($tip, array('写入配置文件', '成功'));
		}

		session('install_step', 4);
		array_push($tip, array('安装完成', ''));

		$this->assign('tip', $tip);
		$this->display();
	}

	public function step4($step = 1){
		session('install_config', null);
		session('install_step', null);
		cookie(null);

		file_write(UPLOAD_PATH . 'install.lock', time());

		$this->display();
	}
}