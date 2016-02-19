<?php
/**
 * 内容管理中的编辑页面表单元素
 * TODO 后期可能会做一个专门的管理页面
 */
return array(
	/* 权限列表(总列表) */
	'auth' => array(
		'view'   => '查看',
		'save'   => '保存',
		'add'    => '添加',
		'edit'   => '编辑',
		'delete' => '删除',
		'top'    => '置顶',
	),

	/* 类型 */
	'type'  => array(
		1 => array(
			'name'  => '页面',                     //名称
			'field' => 'page',                     //对应下面field字段数据，同时使用model对应map
			'url'   => 'Content/page',             //左侧内容列表中打开地址
			'icon'  => 'fa fa-file-powerpoint-o',  //默认图标，如果有自定义则使用自定义
			'auth'  => array('view', 'save'),      //用户权限设置界面自动显示要设置的权限，只能是auth存在的
		),
		2 => array(
			'name'  => '列表',
			'field' => 'article',
			'url'   => 'Content/article',
			'son'   => array(
				'2001'=>array(
					'name'    => '文章',
					'field'   => 'article',
					'url'     => 'Content/article',
					'icon'    => 'fa fa-file-word-o',
					'auth'    => array('view', 'add', 'edit', 'delete', 'top'),
					'toolbar' => array(
						'add' => array(
							'name' => '添加',
							'c'    => 'Content',
							'a'    => 'addArticle',
							'data' => '',
							'icon' => 'fa fa-plus-square-o',
						),
						'edit' => array(
							'name' => '编辑',
							'c'    => 'Content',
							'a'    => 'editArticle',
							'data' => '',
							'icon' => 'fa fa-pencil-square-o',
						),
						'delete' => array(
							'name' => '删除',
							'c'    => 'Content',
							'a'    => 'deleteArticle',
							'data' => '',
							'icon' => 'fa fa-minus-square-o',
						),
						'top' => array(
							'name' => '置顶',
							'c'    => 'Content',
							'a'    => 'topArticle',
							'data' => '',
							'icon' => 'fa fa-check-square-o',
						),
					),
				),
				'2002'=>array(
					'name'  => '新闻',
					'field' => 'news',
					'url'   => 'Content/article',
					'icon'  => 'fa fa-newspaper-o',
					'auth'  => array('view', 'add', 'edit', 'delete'),
					'toolbar' => array(
						'add' => array(
							'name' => '添加',
							'c'    => 'Content',
							'a'    => 'addArticle',
							'data' => '',
							'icon' => 'fa fa-plus-square-o',
						),
						'edit' => array(
							'name' => '编辑',
							'c'    => 'Content',
							'a'    => 'editArticle',
							'data' => '',
							'icon' => 'fa fa-pencil-square-o',
						),
						'delete' => array(
							'name' => '删除',
							'c'    => 'Content',
							'a'    => 'deleteArticle',
							'data' => '',
							'icon' => 'fa fa-minus-square-o',
						),
					),
				),
			),
		),
	),

	/* 映射 TODO 字段名称对应表名 */
	'map' => array(
		'article'  => 'article',
		'news'     => 'article',
	),

	/* 字段 */
	'field' => array(
		/* 页面  */
		'page' => array(
			'status'      => array(
				'name'     => '状态',
				'group'    => '发布设置',
				'editor'   => array('type'=>'combobox','options'=>array('editable'=>false, 'panelHeight'=>'auto','data'=>array(array('text'=>'发布', 'value'=>'发布'),array('text'=>'不发布', 'value'=>'不发布')))),
				'default'  => '发布',
			),
		),

		/* 文章 */
		'article' => array(
			'addtime'      => array(
				'name'      => '添加时间',
				'group'     => '发布设置',
				'editor'    => array('type'=>'datetimebox','options'=>array('tipPosition'=>'left', 'editable'=>false)),
				'default'   => date('Y-m-d H:i:s'),
			),
			'istop'      => array(
				'name'      => '置顶显示',
				'group'     => '发布设置',
				'editor'   => array('type'=>'combobox','options'=>array('editable'=>false, 'panelHeight'=>'auto','data'=>array(array('text'=>'开启', 'value'=>'开启'),array('text'=>'关闭', 'value'=>'关闭')))),
				'default'  => '关闭',
			),
			'status'      => array(
				'name'      => '发布状态',
				'group'     => '发布设置',
				'editor'   => array('type'=>'combobox','options'=>array('editable'=>false, 'panelHeight'=>'auto','data'=>array(array('text'=>'发布', 'value'=>'发布'),array('text'=>'不发布', 'value'=>'不发布')))),
				'default'  => '发布',
			),
			'thumb'       => array(
				'name'      => '缩略图',
				'group'     => '基本属性',
				'editor'    => array('type'=>'image','options'=>array('upload'=>U('Upload/image'), 'multiple'=>false, 'accept'=>'image/*', 'size'=>C('FILE_UPLOAD_IMG_CONFIG.maxSize'), 'crop'=>U('Upload/crop'), 'width'=>240, 'height'=>180, 'subfix'=>'_240x180' )),
			),
			'author'    => array(
				'name'      => '作者',
				'group'     => '基本属性',
				'editor'    => array('type'=>'validatebox','options'=>array('tipPosition'=>'left', 'validType'=>array('length'=>array(0,50)) )),
				'default'   => user_info('realname') ? user_info('realname') : user_info('username'),
			),
			'islink'        => array(
				'name'      => '状态',
				'group'     => '转向链接',
				'editor'   => array('type'=>'combobox','options'=>array('editable'=>false, 'panelHeight'=>'auto','data'=>array(array('text'=>'开启', 'value'=>'开启'),array('text'=>'关闭', 'value'=>'关闭')))),
				'default'  => '关闭',
			),
			'url'         => array(
				'name'      => '链接',
				'group'     => '转向链接',
				'editor'    => array('type'=>'validatebox','options'=>array('tipPosition'=>'left', 'validType'=>array('url','length[0,255]'))),
			),
		),

		/* 新闻 */
		'news' => array(
			'addtime'      => array(
				'name'      => '添加时间',
				'group'     => '发布设置',
				'editor'    => array('type'=>'datetimebox','options'=>array('tipPosition'=>'left', 'editable'=>false )),
				'default'   => date('Y-m-d H:i:s'),
			),
			'istop'      => array(
				'name'      => '置顶显示',
				'group'     => '发布设置',
				'editor'   => array('type'=>'combobox','options'=>array('editable'=>false, 'panelHeight'=>'auto','data'=>array(array('text'=>'开启', 'value'=>'开启'),array('text'=>'关闭', 'value'=>'关闭')))),
				'default'  => '关闭',
			),
			'status'      => array(
				'name'      => '发布状态',
				'group'     => '发布设置',
				'editor'   => array('type'=>'combobox','options'=>array('editable'=>false, 'panelHeight'=>'auto','data'=>array(array('text'=>'发布', 'value'=>'发布'),array('text'=>'不发布', 'value'=>'不发布')))),
				'default'  => '发布',
			),
			'thumb'       => array(
				'name'      => '缩略图',
				'group'     => '基本属性',
				'editor'    => array('type'=>'image','options'=>array('upload'=>U('Upload/image'), 'multiple'=>false, 'accept'=>'image/*', 'size'=>C('FILE_UPLOAD_IMG_CONFIG.maxSize'), 'crop'=>U('Upload/crop'), 'width'=>240, 'height'=>240, 'subfix'=>'_240x240' )),
			),
			'author'    => array(
				'name'      => '作者',
				'group'     => '基本属性',
				'editor'    => array('type'=>'validatebox','options'=>array('tipPosition'=>'left', 'validType'=>array('length'=>array(0,50)) )),
				'default'   => user_info('realname') ? user_info('realname') : user_info('username'),
			),
			'islink'        => array(
				'name'      => '状态',
				'group'     => '转向链接',
				'editor'   => array('type'=>'combobox','options'=>array('editable'=>false, 'panelHeight'=>'auto','data'=>array(array('text'=>'开启', 'value'=>'开启'),array('text'=>'关闭', 'value'=>'关闭')))),
				'default'  => '关闭',
			),
			'url'         => array(
				'name'      => '链接',
				'group'     => '转向链接',
				'editor'    => array('type'=>'validatebox','options'=>array('tipPosition'=>'left', 'validType'=>array('url','length[0,255]'))),
			),
		),
	)
);