<?php
namespace Admin\Controller;
use Think\Controller;

/**
 * 公共控制器
 * @author wangdong
 *
 * TODO
 * 后缀带_iframe的ACTION是在iframe中加载的，用于统一返回格式
 */
class CommonController extends Controller {
	function _initialize(){
		if(IS_AJAX && IS_GET) C('DEFAULT_AJAX_RETURN', 'html');

		self::checkLogin();
		self::checkPriv();
		self::operateLog();
		self::cookie();
	}

	private function checkLogin(){
		if(CONTROLLER_NAME =='Index' && in_array(ACTION_NAME, array('login', 'code'))) return true;

		if(!user_info('userid') || !user_info('roleid')){
			//针对iframe加载返回
			if(IS_GET && strpos(ACTION_NAME,'_iframe') !== false){
				exit('<style type="text/css">body{margin:0;padding:0}a{color:#08c;text-decoration:none}a:hover,a:focus{color:#005580;text-decoration:underline}a:focus,a:hover,a:active{outline:0}</style><div style="padding:6px;font-size:12px">请先<a target="_parent" href="'.U('Index/login').'">登录</a>后台管理</div>');
			}
			if(IS_AJAX && IS_GET){
				exit('<div style="padding:6px">请先<a href="'.U('Index/login').'">登录</a>后台管理</div>');
			}else {
				$this->error('请先登录后台管理', U('Index/login'));
			}
		}
	}

	/**
	 * 权限控制
	 */
	private function checkPriv(){
		if(user_info('roleid') == 1) return true;
		//过滤不需要权限控制的页面
		switch (CONTROLLER_NAME){
			case 'Index':
				switch (ACTION_NAME){
					case 'index':
					case 'login':
					case 'code':
					case 'logout':
						return true;
						break;
				}
				break;
			case 'Upload':
			case 'Content':
				return true;
				break;
		}
		if(strpos(ACTION_NAME, 'public_')!==false) return true;

		$priv_db = M('admin_role_priv');
		$res     = $priv_db->where(array('c'=>CONTROLLER_NAME, 'a'=>ACTION_NAME, 'roleid'=>user_info('roleid')))->find();
		if(!$res){
			//兼容iframe加载
			if(IS_GET && strpos(ACTION_NAME,'_iframe') !== false){
				exit('<style type="text/css">body{margin:0;padding:0}</style><div style="padding:6px;font-size:12px">您没有权限操作该项</div>');
			}
			if(IS_AJAX && IS_GET){
				exit('<div style="padding:6px">您没有权限操作该项</div>');
			}else {
				$this->error('您没有权限操作该项');
			}
		}
	}

	/**
	 * 记录日志
	 */
	private function operateLog(){
		//判断是否记录
		if(C('SAVE_LOG_OPEN')){
			$action = ACTION_NAME;
			if($action == '' || strchr($action,'public') || (CONTROLLER_NAME =='Index' && in_array($action, array('login','code'))) ||  CONTROLLER_NAME =='Upload') {
				return false;
			}else {
				$ip        = get_client_ip(0, true);
				$username  = user_info('username');
				$userid    = user_info('userid');
				$time      = date('Y-m-d H-i-s');
				$data      = array('GET'=>$_GET);
				if(IS_POST) $data['POST'] = $_POST;
				$data      = var_export($data, true);

				$log_db    = M('log');
				$log_db->add(array(
					'username'    => $username,
					'userid'      => $userid,
					'controller'  => CONTROLLER_NAME,
					'action'      => ACTION_NAME,
					'querystring' => $data,
					'time'        => $time,
					'ip'          => $ip
				));
			}
		}
	}

	private function cookie(){
		//记录上次每页显示数
		if(I('get.grid') && I('post.rows')){
			switch(I('get.grid')){
				case 'datagrid':
					cookie('datagrid-pageSize', I('post.rows', 20, 'intVal'));
					break;
				case 'treegrid':
					cookie('treegrid-pageSize', I('post.rows', 2, 'intVal'));
					break;
				case 'propertygrid':
					cookie('propertygrid-pageSize', I('post.rows', 20, 'intVal'));
					break;
			}
		}
	}

	/**
	 * 空操作，用于输出404页面
	 */
	public function _empty(){
		//针对后台ajax请求特殊处理
		if(!IS_AJAX) send_http_status(404);
		if (IS_AJAX && IS_POST){
			$data = array('info'=>'请求地址不存在或已经删除', 'status'=>0, 'total'=>0, 'rows'=>array());
			$this->ajaxReturn($data);
		}else{
			$this->display('Common:404');
		}
	}

	/**
	 * 通用型datagrid页面
	 * @param array $param
	 */
	public function datagrid($param = array()){
		if(CONTROLLER_NAME == 'Common') return $this->_empty();

		$option = array(
			'db'      => null,
			'page'    => 1,
			'rows'    => 10,
			'where'   => array(),
			'sort'    => '',
			'order'   => '',
			'display' => '',
		);
		$option = array_merge($option, $param);

		if(IS_POST){
			$db = $option['db'];

			//排序，支持多个字段
			$sorts  = explode(',', $option['sort']);
			$orders = explode(',', $option['order']);
			$order  = array();
			foreach($sorts as $k=>$sort){
				$order[$sort] = $orders[$k];
			}

			$limit  = ($option['page'] - 1) * $option['rows'] . "," . $option['rows'];
			$total  = $db->where($option['where'])->count();
			$list   = $total ? $db->where($option['where'])->order($order)->limit($limit)->select() : array();

			if(isset($option['formatter'])){
				foreach($list as $key=>&$info){
					foreach($info as $key2=>&$value){
						$option['formatter']($key2, $value, $info);
					}
				}
			}

			$data = array('total'=>$total, 'rows'=>$list);
			$this->ajaxReturn($data);
		}else{
			$menuid     = I('get.menuid');
			$menu_db    = D('Menu');
			$currentpos = $menu_db->currentPos($menuid);  //栏目位置
			$toolbars   = $menu_db->getToolBar($menuid);

			$this->assign('title', $currentpos);
			$this->assign('toolbars', $toolbars);

			if(isset($option['assign']) && is_array($option['assign'])){
				$this->assign($option['assign']);
			}

			$this->display($option['display']);
		}
	}

}
