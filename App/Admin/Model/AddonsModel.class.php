<?php
namespace Admin\Model;
use Think\Model;

class AddonsModel extends Model{
	protected $tableName = 'addons';
	protected $pk        = 'id';

	public function getList(){
		$dirs = array_map('basename', glob(ADDON_PATH.'*', GLOB_ONLYDIR));
		if($dirs === false || !file_exists(ADDON_PATH)){
			$this->error = '插件目录不可读或者不存在';
			return array();
		}

		$addons        = array();
		$list          = $this->select();
		foreach($list as &$info){
			$info['uninstall']     = 0;
			$addons[$info['name']] = $info;
		}

		foreach ($dirs as $value) {
			if(!isset($addons[$value])){
				$class = get_addon_class($value);
				if(!class_exists($class)){ // 实例化插件失败忽略执行
					\Think\Log::record('插件'.$value.'的入口文件不存在！');
					continue;
				}
				$obj            = new $class;
				$addons[$value] = $obj->info;

				if($addons[$value]){
					$addons[$value]['uninstall'] = 1;
					$addons[$value]['status']    = null;
				}
			}
		}

		$result = array();
		$map = array('status'=>array(-1=>'损坏', 0=>'禁用', 1=>'启用', null=>'未安装'));
		foreach ($addons as $key => &$row){
			if(!in_array($key, $dirs)) $row['status'] = -1;

			$row['status_text'] = $map['status'][$row['status']] ?: '-';

			array_push($result, $row);
		}

		return $result;
	}
}