<?php
return array(
	//'配置项'=>'配置值'
	'app_init'  => array('Common\Behavior\InitHookBehavior'), //钩子
	'app_begin' => array('Common\Behavior\SettingBehavior'),  //系统设置
);
?>