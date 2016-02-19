<?php
return array(
	//'配置项'=>'配置值'
	'TMPL_EXCEPTION_FILE'   =>  MODULE_PATH.'View/Common/error.html',// 异常页面的模板文件

	/* 路由设置 */
	'URL_ROUTER_ON'    => true,
	'URL_ROUTE_RULES'  => array(
		'login'    => 'User/login',
		'register' => 'User/register',
		'help'     => 'Index/help',
	),
);