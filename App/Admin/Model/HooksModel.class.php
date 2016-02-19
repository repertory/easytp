<?php
namespace Admin\Model;
use Think\Model;

class HooksModel extends Model {
	protected $tableName = 'hooks';
	protected $pk        = 'id';

	/**
	 * 更新插件里的所有钩子对应的插件
	 */
	public function updateHooks($name){
		$class = get_addon_class($name);//获取插件名
		if(!class_exists($class)){
			$this->error = "未实现{$name}插件的入口文件";
			return false;
		}
		$methods = get_class_methods($class);
		$hooks = $this->getField('name', true);
		$common = array_intersect($hooks, $methods);
		if(!empty($common)){
			foreach ($common as $hook) {
				$flag = $this->updateAddons($hook, array($name));
				if(false === $flag){
					$this->removeHooks($name);
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * 更新单个钩子处的插件
	 */
	public function updateAddons($hook_name, $name){
		$o_addons = $this->where("name='{$hook_name}'")->getField('addons');
		if($o_addons)
			$o_addons = explode(',', $o_addons);
		if($o_addons){
			$addons = array_merge($o_addons, $name);
			$addons = array_unique($addons);
		}else{
			$addons = $name;
		}
		$flag = $this->where(array('name'=>$hook_name))->setField('addons', implode(',', $addons));

		if(false === $flag) $this->where(array('name'=>$hook_name))->setField('addons', implode(',', $o_addons));
		return $flag;
	}

	/**
	 * 去除插件所有钩子里对应的插件数据
	 */
	public function removeHooks($name){
		$addons_class = get_addon_class($name);
		if(!class_exists($addons_class)){
			return false;
		}
		$methods = get_class_methods($addons_class);
		$hooks = $this->getField('name', true);
		$common = array_intersect($hooks, $methods);
		if($common){
			foreach ($common as $hook) {
				$flag = $this->removeAddons($hook, array($name));
				if(false === $flag) return false;
			}
		}
		return true;
	}

	/**
	 * 去除单个钩子里对应的插件数据
	 */
	public function removeAddons($hook_name, $name){
		$o_addons = $this->where(array('name'=>$hook_name))->getField('addons');
		$o_addons = explode(',', $o_addons);
		if($o_addons){
			$addons = array_diff($o_addons, $name);
		}else{
			return true;
		}
		$flag = $this->where(array('name'=>$hook_name))->setField('addons', implode(',', $addons));
		if(false === $flag) $this->where(array('name'=>$hook_name))->setField('addons', implode(',', $o_addons));
		return $flag;
	}
}