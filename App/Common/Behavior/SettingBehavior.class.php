<?php
namespace Common\Behavior;
use Think\Behavior;

class SettingBehavior extends Behavior{
	public function run(&$params){
		if(MODULE_NAME == 'Install'){
			return true;
		}else {
			//自动安装判断
			if(!file_exist(UPLOAD_PATH . 'install.lock')){
				redirect(U('Install/Index/index'));
				exit;
			}
		}

		if(!S('common_behavior_setting')){
			$setting_db = M('setting');
			$list       = $setting_db->getField('name,value,type', true);
			S('common_behavior_setting', $list);
		}else{
			$list = S('common_behavior_setting');
		}

		//使用自定义设置
		if(is_array($list) && !empty($list)){
			foreach ($list as $name=>$config){
				switch (strtolower($config['type'])){
					//数组类型
					case 'array':
						$config['value'] = explode(',', $config['value']);
						break;
				}
				C($name, $config['value']);
			}
		}
	}
}