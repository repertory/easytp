<?php
$config = C('TMPL_PARSE_STRING');
$uploadPath = $config[UPLOAD_PATH];
return array(
	/* 前后端通信相关的配置,注释只允许使用多行方式 */

	/* 上传图片配置项 */
	"imageActionName"     => "uploadimage", /* 执行上传图片的action名称 */
	"imageFieldName"      => "upfile", /* 提交的图片表单名称 */
	"imageMaxSize"        => C('FILE_UPLOAD_IMG_CONFIG.maxSize'), /* 上传大小限制，单位B */
	"imageAllowFiles"     => array_map(function($val){return ".{$val}";}, C('FILE_UPLOAD_IMG_CONFIG.exts')), /* 上传图片格式显示 */
	"imageCompressEnable" => true, /* 是否压缩图片,默认是true */
	"imageCompressBorder" => 1600, /* 图片压缩最长边限制 */
	"imageInsertAlign"    => "none", /* 插入的图片浮动方式 */
	"imageUrlPrefix"      => $uploadPath, /* 图片访问路径前缀 */

	/* 涂鸦图片上传配置项 */
	"scrawlActionName"  => "uploadscrawl", /* 执行上传涂鸦的action名称 */
	"scrawlFieldName"   => "upfile", /* 提交的图片表单名称 */
	"scrawlMaxSize"     => C('FILE_UPLOAD_IMG_CONFIG.maxSize'), /* 上传大小限制，单位B */
	"scrawlUrlPrefix"   => $uploadPath, /* 图片访问路径前缀 */
	"scrawlInsertAlign" => "none",

	/* 截图工具上传 */
	"snapscreenActionName"  => "uploadimage", /* 执行上传截图的action名称 */
	"snapscreenUrlPrefix"   => $uploadPath, /* 图片访问路径前缀 */
	"snapscreenInsertAlign" => "none", /* 插入的图片浮动方式 */

	/* 抓取远程图片配置 */
	"catcherLocalDomain" => array("127.0.0.1", "localhost", "img.baidu.com"),
	"catcherActionName"  => "catchimage", /* 执行抓取远程图片的action名称 */
	"catcherFieldName"   => "source", /* 提交的图片列表表单名称 */
	"catcherUrlPrefix"   => $uploadPath, /* 图片访问路径前缀 */
	"catcherMaxSize"     => C('FILE_UPLOAD_IMG_CONFIG.maxSize'), /* 上传大小限制，单位B */
	"catcherAllowFiles"  => array_map(function($val){return ".{$val}";}, C('FILE_UPLOAD_IMG_CONFIG.exts')), /* 抓取图片格式显示 */

	/* 上传视频配置 */
	"videoActionName" => "uploadvideo", /* 执行上传视频的action名称 */
	"videoFieldName"  => "upfile", /* 提交的视频表单名称 */
	"videoUrlPrefix"  => $uploadPath, /* 视频访问路径前缀 */
	"videoMaxSize"    => C('FILE_UPLOAD_VIDEO_CONFIG.maxSize'), /* 上传大小限制，单位B，默认100MB */
	"videoAllowFiles" => array_map(function($val){return ".{$val}";}, C('FILE_UPLOAD_VIDEO_CONFIG.exts')), /* 上传视频格式显示 */

	/* 上传文件配置 */
	"fileActionName" => "uploadfile", /* controller里,执行上传视频的action名称 */
	"fileFieldName"  => "upfile", /* 提交的文件表单名称 */
	"fileUrlPrefix"  => $uploadPath, /* 文件访问路径前缀 */
	"fileMaxSize"    => C('FILE_UPLOAD_FILE_CONFIG.maxSize'), /* 上传大小限制，单位B，默认50MB */
	"fileAllowFiles" => array_map(function($val){return ".{$val}";}, C('FILE_UPLOAD_FILE_CONFIG.exts')), /* 上传文件格式显示 */

	/* 列出指定目录下的图片 */
	"imageManagerActionName"  => "listimage", /* 执行图片管理的action名称 */
	"imageManagerListPath"    => date('/Y/m/d'), /* 指定要列出图片的目录 */
	"imageManagerListSize"    => 20, /* 每次列出文件数量 */
	"imageManagerUrlPrefix"   => $uploadPath, /* 图片访问路径前缀 */
	"imageManagerInsertAlign" => "none", /* 插入的图片浮动方式 */
	"imageManagerAllowFiles"  => array_map(function($val){return ".{$val}";}, C('FILE_UPLOAD_IMG_CONFIG.exts')), /* 列出的文件类型 */

	/* 列出指定目录下的文件 */
	"fileManagerActionName" => "listfile", /* 执行文件管理的action名称 */
	"fileManagerListPath"   => date('/Y/m/d'), /* 指定要列出文件的目录 */
	"fileManagerUrlPrefix"  => $uploadPath, /* 文件访问路径前缀 */
	"fileManagerListSize"   => 20, /* 每次列出文件数量 */
	"fileManagerAllowFiles" => array_map(function($val){return ".{$val}";}, C('FILE_UPLOAD_FILE_CONFIG.exts')),/* 列出的文件类型 */


);