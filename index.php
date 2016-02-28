<?php
// 检测PHP环境
if(version_compare(PHP_VERSION, '5.3.0','<'))  die('require PHP > 5.3.0 !');

define('DS', DIRECTORY_SEPARATOR);         //简写目录分隔符
define('SITE_DIR', dirname(__FILE__));     //站点目录
define('ADDON_PATH', './Addons/');         //插件目录
define('UPLOAD_PATH', './Public/upload/'); //文件上传根目录

/* 网址信息 */
define('HTTP_REFERER', (!empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '')); //来源页面
define('SCRIPT_DIR', rtrim(dirname($_SERVER['SCRIPT_NAME'] ?: ''), '\/\\') ); //相对地址
if(!empty($_SERVER['HTTP_HOST'])){
	define('SITE_URL', 'http://' . $_SERVER['HTTP_HOST'] . SCRIPT_DIR);       //完整地址
}else{
	define('SITE_URL', SCRIPT_DIR);
}

/* ThinkPHP定义 */
define('APP_DEBUG', true);
define('THINK_PATH', SITE_DIR . DS . 'Libs' . DS . 'ThinkPHP' . DS);
define('APP_PATH', SITE_DIR . DS . 'App' . DS);
define('RUNTIME_PATH', SITE_DIR . DS . '#Runtime' . DS);   //系统运行时目录

require(THINK_PATH.'ThinkPHP.php');