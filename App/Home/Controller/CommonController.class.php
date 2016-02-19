<?php
namespace Home\Controller;
use Think\Controller;
class CommonController extends Controller {
	public function _initialize(){}

	/**
	 * 空操作，用于输出404页面
	 */
	public function _empty(){
		send_http_status(404);
//		$this->show('404 Page Not Found.');
		$this->display('Common:error');
	}
}