<?php
return array(
	/* 文件上传全局配置 */
	'FILE_UPLOAD_CONFIG'    => array(
		'mimes'      => '',                     //允许上传的文件MiMe类型
		'maxSize'    => 5*1024*1024,            //上传的文件大小限制 (0-不做限制)
		'exts'       => array(                  //允许上传的文件后缀
			'png', 'jpg', 'jpeg', 'gif', 'bmp',
			'flv', 'swf', 'mkv', 'avi', 'rm', 'rmvb', 'mpeg', 'mpg',
			'ogg', 'ogv', 'mov', 'wmv', 'mp4', 'webm', 'mp3', 'wav', 'mid',
			'rar', 'zip', 'tar', 'gz', '7z', 'bz2', 'cab', 'iso',
			'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'pdf', 'txt', 'md', 'xml',
		),
		'autoSub'    => true,                   //自动子目录保存文件
		'subName'    => array('date', 'Y/m/d'), //子目录创建方式，[0]-函数名，[1]-参数，多个参数使用数组
		'rootPath'   => UPLOAD_PATH,            //保存根路径
		'savePath'   => '',                     //保存路径
		'saveName'   => array('uniqid', ''),    //上传文件命名规则，[0]-函数名，[1]-参数，多个参数使用数组
		'saveExt'    => '',                     //文件保存后缀，空则使用原后缀
		'replace'    => false,                  //存在同名是否覆盖
		'hash'       => false,                  //是否生成hash编码
		'callback'   => false,                  //检测文件是否存在回调函数，如果存在返回文件信息数组
	),

	/* 单独配置，会覆盖全局配置 */
	'FILE_UPLOAD_FILE_CONFIG' => array(
		'maxSize' => 5*1024*1024,            //上传的文件大小限制 (0-不做限制)
		'exts'    => array(
			'png', 'jpg', 'jpeg', 'gif', 'bmp',
			'flv', 'swf', 'mkv', 'avi', 'rm', 'rmvb', 'mpeg', 'mpg',
			'ogg', 'ogv', 'mov', 'wmv', 'mp4', 'webm', 'mp3', 'wav', 'mid',
			'rar', 'zip', 'tar', 'gz', '7z', 'bz2', 'cab', 'iso',
			'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'pdf', 'txt', 'md', 'xml',
		),
	),
	'FILE_UPLOAD_IMG_CONFIG' => array(
		'maxSize' => 5*1024*1024,            //上传的文件大小限制 (0-不做限制)
		'exts'    => array(
			'png', 'jpg', 'jpeg', 'gif', 'bmp',
		),
	),
	'FILE_UPLOAD_VIDEO_CONFIG' => array(
		'maxSize' => 5*1024*1024,            //上传的文件大小限制 (0-不做限制)
		'exts'    => array(
			'flv', 'swf', 'mkv', 'avi', 'rm', 'rmvb', 'mpeg', 'mpg',
			'ogg', 'ogv', 'mov', 'wmv', 'mp4', 'webm', 'mp3', 'wav', 'mid',
		),
	),
);