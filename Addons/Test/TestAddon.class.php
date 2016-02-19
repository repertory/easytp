<?php
namespace Addons\Test;
use Common\Controller\Addon;

class TestAddon extends Addon{

	public $info = array(
		'name'        => 'Test',
		'title'       => '插件名称',
		'description' => '插件描述',
		'status'      => 1,
		'author'      => 'author',
		'version'     => '0.1',
		'icon'        => 'fa fa-html5',
	);

	public $admin_list = array(
		'list_grid' => array(
			'id:ID',
			'title:文件名',
			'size:大小',
			'update_time_text:更新时间',
			'document_title:文档标题'
		),
		'model'=>'log',
//		'order'=>'id asc',
	);

	public function install(){
		return true;
	}

	public function uninstall(){
		return true;
	}
}
