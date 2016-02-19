<?php
namespace Admin\Model;
use Think\Model;

class SettingModel extends Model{
	protected $tableName = 'setting';
	protected $pk        = 'key';
	
	//获取全部设置信息
	public function getSetting(){
		$result          = array();
		$fields          = dict('', 'Setting'); //获取当前配置选项列表
		$names           = array_keys($fields);
		$where           = array('name'=>array('in', $names));
		$data            = $this->where($where)->getField('name,value', true); //从数据库中获取设置信息
		$result['total'] = count($fields);

		foreach ($fields as $key=>&$arr){
			//如果数据库不存在该设置项则从默认值中获取
			$arr['value'] = array_key_exists($key, $data) ? $data[$key] : $arr['default'];

			if(!empty($arr['dict'])){
				$dict         = array_flip($arr['dict']);
				$arr['value'] = isset($dict[$arr['value']]) ? $dict[$arr['value']] : '';
			}

			$arr['key'] = $key;
		}
		$result['rows'] = array_values($fields);
		
		return $result;
	}

	/**
	 * 保存设置
	 * @param array $info
	 * @return bool
	 */
	public function set($info = array()){
		$fields = dict('', 'Setting'); //获取当前配置选项列表
		$names  = array_keys($fields);
		$this->where(array('key'=>array('not in', $names)))->delete(); //删除多余属性

		$where = array('key'=>array('in', $names));
		$list  = $this->where($where)->getField('name', true); //从数据库中获取设置信息
		if(!is_array($list)) $list = array();
		$result = false;

		foreach ($info as $data){
			if(!empty($fields[$data['name']]['dict'])){
				$dict = $fields[$data['name']]['dict'];
				$data['value'] = isset($dict[$data['value']]) ? $dict[$data['value']] : '';
			}

			if(in_array($data['name'], $list)){
				$state = $this->where(array('name'=>$data['name']))->save($data);
			}else {
				$state = $this->add($data);
			}
			if($state) $result = true;
		}
		$this->clearCatche(); //有修改时清空缓存
		return $result;
	}
	
	//清除设置相关缓存
	public function clearCatche(){
		S('common_behavior_setting', null);
	}
}