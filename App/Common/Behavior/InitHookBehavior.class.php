<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2013 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace Common\Behavior;
use Think\Behavior;
use Think\Hook;

// 初始化钩子信息
class InitHookBehavior extends Behavior {

	// 行为扩展的执行入口必须是run
	public function run(&$content){
		if(!file_exist(UPLOAD_PATH . 'install.lock')) return true;

		$data = S('common_behavior_hooks');
		if(!$data){
			$hooks = M('hooks')->where(array('status'=>1))->getField('name, addons');
			foreach ($hooks as $key => $value) {
				if($value){
					$map['status'] = 1;
					$names         = explode(',', $value);
					$map['name']   = array('IN', $names);
					$data          = M('addons')->where($map)->getField('id, name');
					if($data){
						$addons = array_intersect($names, $data);
						Hook::add($key,array_map('get_addon_class', $addons));
					}
				}
			}
			S('common_behavior_hooks', Hook::get());
		}else{
			Hook::import($data, false);
		}
	}
}