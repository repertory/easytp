<?php
return array(
	//'配置项'=>'配置值'
	'SHOW_PAGE_TRACE'       => false,        //调试配置
	'LOAD_EXT_CONFIG'       => 'file',
	'AUTOLOAD_NAMESPACE'    => array('Addons'=> SITE_DIR.'/Addons'),

	'FILE_UPLOAD_TYPE'      => 'Local',      //上传驱动

	/* 数据库设置 */
	'DB_TYPE'               => 'mysql',      // 数据库类型
	'DB_HOST'               => '127.0.0.1',  // 服务器地址
	'DB_NAME'               => 'easytp',     // 数据库名
	'DB_USER'               => 'root',       // 用户名
	'DB_PWD'                => '',           // 密码
	'DB_PORT'               => '3306',       // 端口
	'DB_PREFIX'             => 'et_',        // 数据库表前缀

	/* URL设置 */
	'MODULE_ALLOW_LIST'     => array('Home', 'Admin', 'Install'),
	'DEFAULT_MODULE'        => 'Home',       // 默认模块
	'URL_CASE_INSENSITIVE'  => true,         // 默认false 表示URL区分大小写 true则表示不区分大小写
	'URL_MODEL'             => 2,            // URL模式

	/* 模板标签设置 */
	'TMPL_L_DELIM'          => '<{',         // 模板引擎普通标签开始标记
	'TMPL_R_DELIM'          => '}>',         // 模板引擎普通标签结束标记

	/* 模板解析设置 */
	'TMPL_PARSE_STRING'     => array(
		'./Public/upload/'  => SCRIPT_DIR . '/Public/upload/',
		'__PUBLIC__'        => SCRIPT_DIR . '/Public',
		'__STATIC__'        => SCRIPT_DIR . '/Public/static',
		'__VERSION__'       => date('YmdHi'),
	),

	/* 邮箱配置 */
	'EMAIL_CONFIG'          => array(
		'smtp'     => 'smtp.qq.com',
		'port'     => 25,
		'from'     => '531381545@qq.com',
		'user'     => '531381545@qq.com',
		'password' => '',
		'report'   => 'admin@admin.com', //报警接收邮箱
	),

	/* 水印配置 */
	'IMAGE_WATER_CONFIG'    => array(
		'status'   => 0,         //状态
		'type'     => 0,         //模式 1为图片 0为文字
		'text'     => 'EASYTP',  //水印文字
		'image'    => './Public/static/img/logo.png',  //水印图片
		'position' => 9,         //九宫格位置
		'x'        => -5,        //x轴偏移
		'y'        => -5,        //y轴偏移
		'size'     => 30,        //水印文字大小
		'color'    => '#305697', //水印文字颜色
	),

	/* 接口设置 */
	'API_SIGN'              => '04B29480233F4DEF5C875875B6BDC3B1', //接口签名
);