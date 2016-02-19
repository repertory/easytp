<?php
return array(
	//'配置项'=>'配置值'
	'SYSTEM_NAME'           => 'EASYTP管理系统',
	'SYSTEM_VERSION'        => '2.0.0[dev]',

	'SHOW_PAGE_TRACE'       => false,

	'TMPL_ACTION_ERROR'     =>  MODULE_PATH.'View/Common/dispatch_jump.html', // 默认错误跳转对应的模板文件
	'TMPL_ACTION_SUCCESS'   =>  MODULE_PATH.'View/Common/dispatch_jump.html', // 默认成功跳转对应的模板文件
	'TMPL_EXCEPTION_FILE'   =>  MODULE_PATH.'View/Common/think_exception.html',// 异常页面的模板文件

	/* 后台自定义设置 */
	'SAVE_LOG_OPEN'         => 0,          //开启后台日志记录
	'MAX_LOGIN_TIMES'       => 9,          //最大登录失败次数，防止为0时不能登录，因此不包含第一次登录
	'LOGIN_WAIT_TIME'       => 60,         //登录次数达到后需要等待时间才能再次登录，单位：分钟
	'LOGIN_ONLY_ONE'        => 0,          //开启单设备登录
	'DATAGRID_PAGE_SIZE'    => 20,         //列表默认分页数
	'CATEGORY_LEVEL'        => 3,          //栏目级数，防止太多影响效率
);